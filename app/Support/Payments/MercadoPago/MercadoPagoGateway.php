<?php

namespace App\Support\Payments\MercadoPago;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\IntegrationSetting;
use App\Support\Integrations\ConnectionResult;
use App\Support\Payments\PaymentCapabilities;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentResult;
use App\Support\Payments\WebhookResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class MercadoPagoGateway implements PaymentGateway
{
    private const BASE_URL = 'https://api.mercadopago.com';

    public function __construct(private readonly IntegrationSetting $integration) {}

    public function capabilities(): PaymentCapabilities
    {
        return new PaymentCapabilities(pix: true, creditCard: true, installments: true, refund: true);
    }

    public function createPayment(PaymentRequest $request): PaymentResult
    {
        if ($request->method === 'mercado_pago') {
            return $this->createHostedPayment($request);
        }

        if (! in_array($request->method, $this->capabilities()->methods(), true)) {
            throw new RuntimeException('Método de pagamento não suportado pelo Mercado Pago.');
        }

        if ($request->method === 'credit_card' && blank($request->token)) {
            throw new RuntimeException('O token seguro do cartão não foi informado.');
        }

        $order = $request->order;
        $paymentMethod = $request->method === 'pix'
            ? ['type' => 'bank_transfer', 'id' => 'pix']
            : array_filter([
                'type' => 'credit_card',
                'id' => $request->paymentMethodId,
                'token' => $request->token,
                'installments' => $request->installments,
            ], fn (mixed $value): bool => filled($value));

        $response = $this->request()
            ->withHeader('X-Idempotency-Key', $request->payment->idempotency_key)
            ->post('/v1/orders', [
                'type' => 'online',
                'processing_mode' => 'automatic',
                'external_reference' => $order->code,
                'total_amount' => number_format($request->payment->amount_cents / 100, 2, '.', ''),
                'payer' => ['email' => $order->customer_email],
                'transactions' => ['payments' => [[
                    'amount' => number_format($request->payment->amount_cents / 100, 2, '.', ''),
                    'payment_method' => $paymentMethod,
                ]]],
                'notification_url' => $this->publicUrl(),
            ]);

        if ($response->failed()) {
            $error = $response->json();
            $errorCode = data_get($error, 'error');

            Log::warning('Mercado Pago order creation failed.', [
                'provider' => 'mercado_pago',
                'environment' => $this->integration->environment,
                'order_id' => $order->id,
                'payment_id' => $request->payment->id,
                'method' => $request->method,
                'endpoint' => '/v1/orders',
                'http_status' => $response->status(),
                'provider_error_code' => is_string($errorCode) && preg_match('/^[A-Za-z0-9_.-]{1,80}$/', $errorCode)
                    ? $errorCode
                    : null,
                'provider_cause_codes' => collect(data_get($error, 'cause', []))
                    ->pluck('code')
                    ->filter(fn (mixed $code): bool => is_int($code) || (is_string($code) && preg_match('/^[A-Za-z0-9_.-]{1,40}$/', $code)))
                    ->take(5)
                    ->values()
                    ->all(),
                'provider_message' => $this->sanitizeProviderMessage(data_get($error, 'message')),
                'provider_request_id' => $response->header('x-request-id'),
            ]);

            throw new RuntimeException('Não foi possível criar o pagamento no Mercado Pago.');
        }

        $data = $response->json();
        $payment = data_get($data, 'transactions.payments.0', []);

        return new PaymentResult(
            providerPaymentId: (string) (data_get($payment, 'id') ?: data_get($data, 'id')),
            status: $this->mapStatus((string) (data_get($payment, 'status') ?: data_get($data, 'status'))),
            externalStatus: (string) (data_get($payment, 'status') ?: data_get($data, 'status')),
            pixCode: data_get($payment, 'payment_method.qr_code') ?: data_get($payment, 'point_of_interaction.transaction_data.qr_code'),
            pixQrCodeBase64: data_get($payment, 'payment_method.qr_code_base64') ?: data_get($payment, 'point_of_interaction.transaction_data.qr_code_base64'),
            expiresAt: data_get($payment, 'date_of_expiration'),
            metadata: ['order_id' => data_get($data, 'id')],
        );
    }

    public function findPayment(string $providerPaymentId): PaymentResult
    {
        $response = $this->request()->get("/v1/payments/{$providerPaymentId}");

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível consultar o pagamento no Mercado Pago.');
        }

        return $this->fromLegacyPayment($response->json());
    }

    public function cancel(string $providerPaymentId): PaymentResult
    {
        $response = $this->request()->put("/v1/payments/{$providerPaymentId}", ['status' => 'cancelled']);

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível cancelar o pagamento no Mercado Pago.');
        }

        return $this->fromLegacyPayment($response->json());
    }

    public function refund(string $providerPaymentId, ?int $amountCents = null): PaymentResult
    {
        $payload = $amountCents ? ['amount' => $amountCents / 100] : [];
        $response = $this->request()->withHeader('X-Idempotency-Key', (string) str()->uuid())
            ->post("/v1/payments/{$providerPaymentId}/refunds", $payload);

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível estornar o pagamento no Mercado Pago.');
        }

        return new PaymentResult($providerPaymentId, PaymentStatus::Refunded, 'refunded');
    }

    public function validateWebhook(Request $request): bool
    {
        $secret = (string) $this->integration->credential('webhook_secret');

        if ($secret === '') {
            return app()->environment('testing');
        }

        $signature = (string) $request->header('x-signature', '');
        $requestId = (string) $request->header('x-request-id', '');
        preg_match('/(?:^|,)ts=([^,]+)/', $signature, $timestamp);
        preg_match('/(?:^|,)v1=([^,]+)/', $signature, $hash);
        $dataId = (string) ($request->query('data.id') ?: data_get($request->all(), 'data.id'));

        if ($dataId === '' || $requestId === '' || empty($timestamp[1]) || empty($hash[1])) {
            return false;
        }

        $manifest = 'id:'.strtolower($dataId).";request-id:{$requestId};ts:{$timestamp[1]};";

        return hash_equals(hash_hmac('sha256', $manifest, $secret), trim($hash[1]));
    }

    public function handleWebhook(Request $request): WebhookResult
    {
        $resourceId = (string) ($request->query('data.id') ?: data_get($request->all(), 'data.id') ?: $request->input('id'));
        $type = (string) ($request->input('type') ?: $request->query('type') ?: 'payment');
        $result = $type === 'order' ? $this->findOrderPayment($resourceId) : $this->findPayment($resourceId);

        return new WebhookResult(
            eventId: $resourceId.':'.$result->externalStatus,
            eventType: $type,
            providerPaymentId: $result->providerPaymentId,
            status: $result->status,
            externalStatus: $result->externalStatus,
            metadata: $result->metadata,
        );
    }

    public function testConnection(): ConnectionResult
    {
        try {
            $response = $this->request()->get('/users/me');

            return $response->successful()
                ? ConnectionResult::success('Mercado Pago conectado.')
                : ConnectionResult::failure('Credenciais do Mercado Pago inválidas.');
        } catch (\Throwable) {
            return ConnectionResult::failure('Não foi possível conectar ao Mercado Pago.');
        }
    }

    private function createHostedPayment(PaymentRequest $request): PaymentResult
    {
        $client = app()->make(MercadoPagoClient::class);
        $preference = $client->createPreference($request->order);
        $redirect = $this->integration->environment === 'sandbox'
            ? ($preference['sandbox_init_point'] ?? $preference['init_point'] ?? null)
            : ($preference['init_point'] ?? null);

        return new PaymentResult(
            providerPaymentId: (string) ($preference['id'] ?? ''),
            status: PaymentStatus::Pending,
            externalStatus: 'pending',
            redirectUrl: $redirect,
            metadata: ['preference_id' => $preference['id'] ?? null],
        );
    }

    private function fromLegacyPayment(array $data): PaymentResult
    {
        $status = (string) data_get($data, 'status');

        return new PaymentResult(
            providerPaymentId: (string) data_get($data, 'id'),
            status: $this->mapStatus($status),
            externalStatus: $status,
            pixCode: data_get($data, 'point_of_interaction.transaction_data.qr_code'),
            pixQrCodeBase64: data_get($data, 'point_of_interaction.transaction_data.qr_code_base64'),
            expiresAt: data_get($data, 'date_of_expiration'),
            metadata: [
                'external_reference' => data_get($data, 'external_reference'),
                'status_detail' => data_get($data, 'status_detail'),
            ],
        );
    }

    private function findOrderPayment(string $orderId): PaymentResult
    {
        $response = $this->request()->get("/v1/orders/{$orderId}");

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível consultar o pedido de pagamento no Mercado Pago.');
        }

        $data = $response->json();
        $payment = (array) data_get($data, 'transactions.payments.0', []);
        $status = (string) (data_get($payment, 'status') ?: data_get($data, 'status'));

        return new PaymentResult(
            providerPaymentId: (string) (data_get($payment, 'id') ?: $orderId),
            status: $this->mapStatus($status),
            externalStatus: $status,
            pixCode: data_get($payment, 'payment_method.qr_code'),
            pixQrCodeBase64: data_get($payment, 'payment_method.qr_code_base64'),
            metadata: [
                'external_reference' => data_get($data, 'external_reference'),
                'order_id' => data_get($data, 'id'),
            ],
        );
    }

    private function mapStatus(string $status): PaymentStatus
    {
        return match ($status) {
            'approved' => PaymentStatus::Paid,
            'in_process', 'authorized' => PaymentStatus::Processing,
            'rejected' => PaymentStatus::Failed,
            'cancelled' => PaymentStatus::Cancelled,
            'refunded', 'charged_back' => PaymentStatus::Refunded,
            default => PaymentStatus::Pending,
        };
    }

    private function sanitizeProviderMessage(mixed $message): ?string
    {
        if (! is_string($message) || trim($message) === '') {
            return null;
        }

        $message = strip_tags($message);
        $message = preg_replace('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', '[redacted-email]', $message) ?? '';
        $message = preg_replace('/(?<!\d)(?:\d[.\-\s]?){10,19}(?!\d)/', '[redacted-number]', $message) ?? '';
        $message = preg_replace('/\b(authorization)\b\s*[:=]\s*(?:Bearer\s+)?[^\s,;]+/i', '$1=[REDACTED]', $message) ?? '';
        $message = preg_replace('/\b(bearer)\s+[A-Za-z0-9._~+\/=-]+/i', '$1 [REDACTED]', $message) ?? '';
        $message = preg_replace('/\b(cvv|cvc|security[_\s-]?code)\b\s*[:=]\s*\d{3,4}\b/i', '$1=[REDACTED]', $message) ?? '';
        $message = preg_replace('/\b(access[_\s-]?token|public[_\s-]?key|card[_\s-]?token|webhook[_\s-]?secret)\b\s*[:=]\s*[^\s,;]+/i', '$1=[REDACTED]', $message) ?? '';

        return mb_substr(trim($message), 0, 300) ?: null;
    }

    private function request(): PendingRequest
    {
        $token = (string) $this->integration->credential('access_token');

        if ($token === '') {
            throw new RuntimeException('Access Token do Mercado Pago não configurado.');
        }

        return Http::baseUrl(self::BASE_URL)->acceptJson()->asJson()->withToken($token)->timeout(20);
    }

    private function publicUrl(): ?string
    {
        $url = route('webhooks.payments', ['provider' => 'mercado-pago']);

        return str_starts_with($url, 'https://') && ! str_contains($url, 'localhost') ? $url : null;
    }
}
