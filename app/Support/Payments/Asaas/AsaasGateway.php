<?php

namespace App\Support\Payments\Asaas;

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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AsaasGateway implements PaymentGateway
{
    public function __construct(private readonly IntegrationSetting $integration) {}

    public function capabilities(): PaymentCapabilities
    {
        return new PaymentCapabilities(pix: true, creditCard: true, refund: true);
    }

    public function createPayment(PaymentRequest $request): PaymentResult
    {
        if (! in_array($request->method, ['pix', 'credit_card'], true)) {
            throw new RuntimeException('Método de pagamento não suportado pelo Asaas.');
        }

        return Cache::lock('asaas-payment-'.hash('sha256', $request->payment->idempotency_key), 20)
            ->block(5, fn (): PaymentResult => $this->createPaymentOnce($request));
    }

    private function createPaymentOnce(PaymentRequest $request): PaymentResult
    {
        $customerId = $this->findOrCreateCustomer($request->order->customer_email, $request->order->customer_name);
        $billingType = $request->method === 'pix' ? 'PIX' : 'CREDIT_CARD';
        $existing = $this->request()->get('/v3/payments', [
            'externalReference' => $request->order->code,
            'billingType' => $billingType,
            'limit' => 1,
        ]);
        $data = (array) data_get($existing->json(), 'data.0', []);

        if ($existing->successful() && filled($data['id'] ?? null)) {
            return $this->paymentResult($data, $request->method);
        }

        $response = $this->request()->post('/v3/payments', [
            'customer' => $customerId,
            'billingType' => $billingType,
            'value' => round($request->payment->amount_cents / 100, 2),
            'dueDate' => now()->addDay()->toDateString(),
            'description' => "Pedido {$request->order->code}",
            'externalReference' => $request->order->code,
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível criar o pagamento no Asaas.');
        }

        return $this->paymentResult($response->json(), $request->method);
    }

    private function paymentResult(array $data, string $method): PaymentResult
    {
        $pix = [];

        if ($method === 'pix') {
            $pixResponse = $this->request()->get('/v3/payments/'.data_get($data, 'id').'/pixQrCode');
            $pix = $pixResponse->successful() ? $pixResponse->json() : [];
        }

        return new PaymentResult(
            providerPaymentId: (string) data_get($data, 'id'),
            status: $this->mapStatus((string) data_get($data, 'status')),
            externalStatus: (string) data_get($data, 'status'),
            redirectUrl: $method === 'credit_card' ? data_get($data, 'invoiceUrl') : null,
            pixCode: data_get($pix, 'payload'),
            pixQrCodeBase64: data_get($pix, 'encodedImage'),
            expiresAt: data_get($pix, 'expirationDate'),
            metadata: ['invoice_url' => data_get($data, 'invoiceUrl')],
        );
    }

    public function findPayment(string $providerPaymentId): PaymentResult
    {
        $response = $this->request()->get("/v3/payments/{$providerPaymentId}");

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível consultar o pagamento no Asaas.');
        }

        return $this->fromPayment($response->json());
    }

    public function cancel(string $providerPaymentId): PaymentResult
    {
        $response = $this->request()->delete("/v3/payments/{$providerPaymentId}");

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível cancelar o pagamento no Asaas.');
        }

        return new PaymentResult($providerPaymentId, PaymentStatus::Cancelled, 'DELETED');
    }

    public function refund(string $providerPaymentId, ?int $amountCents = null): PaymentResult
    {
        $response = $this->request()->post("/v3/payments/{$providerPaymentId}/refund", array_filter([
            'value' => $amountCents ? round($amountCents / 100, 2) : null,
        ]));

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível estornar o pagamento no Asaas.');
        }

        return new PaymentResult($providerPaymentId, PaymentStatus::Refunded, 'REFUNDED');
    }

    public function validateWebhook(Request $request): bool
    {
        $configured = (string) $this->integration->credential('webhook_token');

        if ($configured === '') {
            return app()->environment('testing');
        }

        return hash_equals($configured, (string) $request->header('asaas-access-token'));
    }

    public function handleWebhook(Request $request): WebhookResult
    {
        $payment = (array) $request->input('payment', []);
        $providerPaymentId = (string) data_get($payment, 'id');
        $event = (string) $request->input('event', 'PAYMENT_UPDATED');
        $status = $this->mapStatus((string) data_get($payment, 'status'));

        return new WebhookResult(
            eventId: (string) ($request->input('id') ?: $event.':'.$providerPaymentId),
            eventType: $event,
            providerPaymentId: $providerPaymentId,
            status: $status,
            externalStatus: (string) data_get($payment, 'status'),
            metadata: ['external_reference' => data_get($payment, 'externalReference')],
        );
    }

    public function testConnection(): ConnectionResult
    {
        try {
            $response = $this->request()->get('/v3/myAccount');

            return $response->successful()
                ? ConnectionResult::success('Asaas conectado.')
                : ConnectionResult::failure('Credenciais do Asaas inválidas.');
        } catch (\Throwable) {
            return ConnectionResult::failure('Não foi possível conectar ao Asaas.');
        }
    }

    private function findOrCreateCustomer(string $email, string $name): string
    {
        $lookup = $this->request()->get('/v3/customers', ['email' => $email, 'limit' => 1]);
        $existing = (string) data_get($lookup->json(), 'data.0.id');

        if ($lookup->successful() && $existing !== '') {
            return $existing;
        }

        $response = $this->request()->post('/v3/customers', ['name' => $name, 'email' => $email]);

        if ($response->failed() || blank($response->json('id'))) {
            throw new RuntimeException('Não foi possível registrar o cliente no Asaas.');
        }

        return (string) $response->json('id');
    }

    private function fromPayment(array $data): PaymentResult
    {
        $status = (string) data_get($data, 'status');

        return new PaymentResult(
            providerPaymentId: (string) data_get($data, 'id'),
            status: $this->mapStatus($status),
            externalStatus: $status,
            redirectUrl: data_get($data, 'invoiceUrl'),
            metadata: ['external_reference' => data_get($data, 'externalReference')],
        );
    }

    private function mapStatus(string $status): PaymentStatus
    {
        return match ($status) {
            'RECEIVED', 'CONFIRMED', 'RECEIVED_IN_CASH' => PaymentStatus::Paid,
            'PENDING' => PaymentStatus::Pending,
            'OVERDUE' => PaymentStatus::Failed,
            'REFUNDED', 'PARTIALLY_REFUNDED', 'REFUND_REQUESTED' => PaymentStatus::Refunded,
            'DELETED' => PaymentStatus::Cancelled,
            default => PaymentStatus::Processing,
        };
    }

    private function request(): PendingRequest
    {
        $apiKey = (string) $this->integration->credential('api_key');

        if ($apiKey === '') {
            throw new RuntimeException('API key do Asaas não configurada.');
        }

        $url = $this->integration->environment === 'production'
            ? 'https://api.asaas.com'
            : 'https://api-sandbox.asaas.com';

        return Http::baseUrl($url)
            ->acceptJson()
            ->asJson()
            ->withHeader('access_token', $apiKey)
            ->withUserAgent(config('app.name').'/'.config('app.version', '1.0'))
            ->timeout(20);
    }
}
