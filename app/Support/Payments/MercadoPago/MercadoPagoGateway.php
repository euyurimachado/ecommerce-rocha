<?php

namespace App\Support\Payments\MercadoPago;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\IntegrationSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Integrations\ConnectionResult;
use App\Support\Payments\PaymentCapabilities;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentResult;
use App\Support\Payments\WebhookResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        $key = hash('sha256', (string) $request->payment->idempotency_key);

        return Cache::lock('mercado-pago-payment-'.$key, 35)
            ->block(10, fn (): PaymentResult => $this->createPaymentOnce($request));
    }

    public function reconcilePayment(Order $order, Payment $payment): ?PaymentResult
    {
        $key = hash('sha256', (string) $payment->idempotency_key);

        return Cache::lock('mercado-pago-payment-'.$key, 35)->block(10, function () use ($order, $payment): ?PaymentResult {
            if (filled($payment->provider_payment_id)) {
                return $this->findPayment((string) $payment->provider_payment_id);
            }

            return $this->searchPaymentByExternalReference($order, $payment);
        });
    }

    private function createPaymentOnce(PaymentRequest $request): PaymentResult
    {
        if (filled($request->payment->provider_payment_id)) {
            return $this->findPayment((string) $request->payment->provider_payment_id);
        }

        if ($request->reconcileFirst) {
            $existing = $this->searchPaymentByExternalReference($request->order, $request->payment);
            if ($existing) {
                return $existing;
            }
        }

        if (! in_array($request->method, $this->capabilities()->methods(), true)) {
            throw new RuntimeException('Método de pagamento não suportado pelo Mercado Pago.');
        }

        if ($request->method === 'credit_card' && (blank($request->token) || blank($request->paymentMethodId))) {
            throw new RuntimeException('Não foi possível validar o cartão. Confira os dados e tente novamente.');
        }

        $order = $request->order;
        $name = preg_split('/\s+/', trim($order->customer_name), 2) ?: [];
        $payer = array_filter([
            'email' => $order->customer_email,
            'first_name' => $name[0] ?? null,
            'last_name' => $name[1] ?? null,
        ], fn (mixed $value): bool => filled($value));

        $identificationNumber = preg_replace('/\D+/', '', (string) ($request->identificationNumber ?: $order->customer_tax_id));
        $identificationType = $request->identificationType ?: match (strlen($identificationNumber)) {
            11 => 'CPF',
            14 => 'CNPJ',
            default => null,
        };
        if ($identificationNumber !== '' && $identificationType) {
            $payer['identification'] = ['type' => $identificationType, 'number' => $identificationNumber];
        }

        $payload = [
            'transaction_amount' => round($request->payment->amount_cents / 100, 2),
            'description' => 'Pedido '.$order->code.' - '.(string) config('app.name'),
            'external_reference' => $order->code,
            'payment_method_id' => $request->method === 'pix' ? 'pix' : $request->paymentMethodId,
            'payer' => $payer,
        ];

        if ($request->method === 'credit_card') {
            $payload += [
                'token' => $request->token,
                'installments' => $request->installments,
            ];
            if (filled($request->issuerId) && ctype_digit((string) $request->issuerId)) {
                $payload['issuer_id'] = (int) $request->issuerId;
            }
        }

        if ($notificationUrl = $this->publicUrl()) {
            $payload['notification_url'] = $notificationUrl;
        }

        $idempotencyFingerprint = substr(hash('sha256', (string) $request->payment->idempotency_key), 0, 16);
        $response = $this->request()
            ->withHeader('X-Idempotency-Key', $request->payment->idempotency_key)
            ->post('/v1/payments', $payload);

        if ($response->failed()) {
            $error = $response->json();
            $errorCode = data_get($error, 'error');
            Log::warning('Mercado Pago payment creation failed.', [
                'provider' => 'mercado_pago',
                'environment' => $this->integration->environment,
                'order_id' => $order->id,
                'payment_id' => $request->payment->id,
                'method' => $request->method,
                'endpoint' => '/v1/payments',
                'http_status' => $response->status(),
                'idempotency_fingerprint' => $idempotencyFingerprint,
                'provider_error_code' => is_string($errorCode) && preg_match('/^[A-Za-z0-9_.-]{1,80}$/', $errorCode) ? $errorCode : null,
                'provider_causes' => collect(data_get($error, 'cause', []))->take(5)->map(fn (mixed $cause): array => array_filter([
                    'code' => is_int(data_get($cause, 'code')) || (is_string(data_get($cause, 'code')) && preg_match('/^[A-Za-z0-9_.-]{1,40}$/', data_get($cause, 'code'))) ? data_get($cause, 'code') : null,
                    'description' => $this->sanitizeProviderMessage(data_get($cause, 'description')),
                ], fn (mixed $value): bool => $value !== null))->values()->all(),
                'provider_message' => $this->sanitizeProviderMessage(data_get($error, 'message')),
                'provider_request_id' => $response->header('x-request-id'),
            ]);

            throw new RuntimeException('Não foi possível criar o pagamento no Mercado Pago.');
        }

        return $this->fromPayment($response->json());
    }

    private function searchPaymentByExternalReference(Order $order, Payment $payment): ?PaymentResult
    {
        $response = $this->request()->get('/v1/payments/search', [
            'external_reference' => $order->code,
            'sort' => 'date_created',
            'criteria' => 'desc',
            'limit' => 10,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível verificar o pagamento existente no Mercado Pago.');
        }

        $results = collect($response->json('results', []))
            ->filter(fn (mixed $candidate): bool => is_array($candidate)
                && (string) data_get($candidate, 'external_reference') === $order->code
                && (int) round(((float) data_get($candidate, 'transaction_amount', 0)) * 100) === (int) $payment->amount_cents
                && $this->matchesPaymentMethod($candidate, $payment->method))
            ->values();

        if ($results->count() > 1) {
            throw new RuntimeException('Há mais de um pagamento associado a esta tentativa; é necessária reconciliação manual.');
        }

        return $results->isEmpty() ? null : $this->fromPayment($results->first());
    }

    private function matchesPaymentMethod(array $payment, string $method): bool
    {
        return $method === 'credit_card'
            ? data_get($payment, 'payment_type_id') === 'credit_card'
            : data_get($payment, 'payment_method_id') === 'pix';
    }

    private function safeProviderCode(mixed $value): string
    {
        return is_string($value) && preg_match('/^[a-zA-Z0-9_.-]{1,64}$/', $value)
            ? $value
            : 'unavailable';
    }

    public function findPayment(string $providerPaymentId): PaymentResult
    {
        $response = $this->request()->get("/v1/payments/{$providerPaymentId}");

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível consultar o pagamento no Mercado Pago.');
        }

        return $this->fromPayment($response->json());
    }

    public function cancel(string $providerPaymentId): PaymentResult
    {
        $response = $this->request()->put("/v1/payments/{$providerPaymentId}", ['status' => 'cancelled']);

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível cancelar o pagamento no Mercado Pago.');
        }

        return $this->fromPayment($response->json());
    }

    public function refund(string $providerPaymentId, ?int $amountCents = null): PaymentResult
    {
        $payload = $amountCents ? ['amount' => $amountCents / 100] : [];
        $refundKey = hash('sha256', 'mercado-pago-refund:'.$providerPaymentId.':'.($amountCents ?? 'full'));
        $response = $this->request()->withHeader('X-Idempotency-Key', $refundKey)
            ->post("/v1/payments/{$providerPaymentId}/refunds", $payload);

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível estornar o pagamento no Mercado Pago.');
        }

        return $this->findPayment($providerPaymentId);
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

    private function fromPayment(array $data): PaymentResult
    {
        $status = (string) data_get($data, 'status', 'pending');
        $transaction = 'point_of_interaction.transaction_data.';

        return new PaymentResult(
            providerPaymentId: (string) data_get($data, 'id'),
            status: $this->mapStatus($status),
            externalStatus: $status,
            pixCode: data_get($data, $transaction.'qr_code'),
            pixQrCodeBase64: data_get($data, $transaction.'qr_code_base64'),
            expiresAt: data_get($data, 'date_of_expiration'),
            metadata: array_filter([
                'external_reference' => data_get($data, 'external_reference'),
                'status_detail' => data_get($data, 'status_detail'),
                'ticket_url' => data_get($data, $transaction.'ticket_url'),
            ], fn (mixed $value): bool => $value !== null),
            pixTicketUrl: data_get($data, $transaction.'ticket_url'),
        );
    }

    private function findOrderPayment(string $orderId): PaymentResult
    {
        $response = $this->request()->get("/v1/orders/{$orderId}");
        if ($response->failed()) {
            throw new RuntimeException('Não foi possível consultar o pedido de pagamento no Mercado Pago.');
        }

        $paymentId = data_get($response->json(), 'transactions.payments.0.id');
        if (! filled($paymentId)) {
            throw new RuntimeException('O pedido antigo não contém um pagamento consultável.');
        }

        return $this->findPayment((string) $paymentId);
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
