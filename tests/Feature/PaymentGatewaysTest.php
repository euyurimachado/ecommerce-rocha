<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\IntegrationSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Payments\PaymentGatewayManager;
use App\Support\Payments\PaymentRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class PaymentGatewaysTest extends TestCase
{
    use RefreshDatabase;

    public function test_mercado_pago_creates_pix_and_tokenized_card_with_idempotency(): void
    {
        $integration = $this->integration('mercado_pago', ['access_token' => 'TEST-token', 'public_key' => 'TEST-public']);
        $order = $this->order();
        Http::fake(['api.mercadopago.com/v1/orders' => Http::sequence()
            ->push(['id' => 'order-pix', 'status' => 'action_required', 'transactions' => ['payments' => [[
                'id' => 'pay-pix', 'status' => 'pending', 'payment_method' => ['qr_code' => 'pix-code', 'qr_code_base64' => 'base64'],
            ]]]], 201)
            ->push(['id' => 'order-card', 'status' => 'processed', 'transactions' => ['payments' => [[
                'id' => 'pay-card', 'status' => 'approved',
            ]]]], 201)]);
        $gateway = app(PaymentGatewayManager::class)->for('mercado_pago', $integration);

        $pixPayment = $this->payment($order, 'pix');
        $pix = $gateway->createPayment(new PaymentRequest($order, $pixPayment, 'pix'));
        $cardPayment = $this->payment($order, 'credit_card');
        $card = $gateway->createPayment(new PaymentRequest($order, $cardPayment, 'credit_card', 'secure-card-token', 'master', 3));

        $this->assertSame('pix-code', $pix->pixCode);
        $this->assertSame(PaymentStatus::Paid, $card->status);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.mercadopago.com/v1/orders'
            && filled($request->header('X-Idempotency-Key')[0] ?? null));
        Http::assertSent(fn (Request $request): bool => data_get($request->data(), 'transactions.payments.0.payment_method.token') === 'secure-card-token');
    }

    public function test_asaas_creates_pix_and_hosted_credit_card_without_card_data(): void
    {
        $integration = $this->integration('asaas', ['api_key' => 'asaas-secret', 'webhook_token' => 'hook-secret']);
        $order = $this->order();
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/v3/customers?')) {
                return Http::response(['data' => [['id' => 'cus-1']]]);
            }
            if (str_ends_with($request->url(), '/pixQrCode')) {
                return Http::response(['payload' => 'pix-payload', 'encodedImage' => 'pix-image']);
            }
            if (str_ends_with($request->url(), '/v3/payments')) {
                return Http::response([
                    'id' => $request['billingType'] === 'PIX' ? 'pay-pix' : 'pay-card',
                    'status' => 'PENDING', 'invoiceUrl' => 'https://sandbox.asaas.com/i/hosted',
                ], 200);
            }

            return Http::response([], 404);
        });
        $gateway = app(PaymentGatewayManager::class)->for('asaas', $integration);
        $pix = $gateway->createPayment(new PaymentRequest($order, $this->payment($order, 'pix'), 'pix'));
        $card = $gateway->createPayment(new PaymentRequest($order, $this->payment($order, 'credit_card'), 'credit_card'));

        $this->assertSame('pix-payload', $pix->pixCode);
        $this->assertSame('https://sandbox.asaas.com/i/hosted', $card->redirectUrl);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/v3/payments')
            && ! isset($request['creditCard']) && ! isset($request['creditCardHolderInfo']));
    }

    public function test_asaas_webhook_is_authenticated_and_idempotent(): void
    {
        $this->integration('asaas', ['api_key' => 'asaas-secret', 'webhook_token' => 'hook-secret']);
        $order = $this->order();
        $payment = $this->payment($order, 'pix');
        $payment->update(['provider' => 'asaas', 'provider_payment_id' => 'pay-123']);
        $payload = ['id' => 'evt-1', 'event' => 'PAYMENT_CONFIRMED', 'payment' => [
            'id' => 'pay-123', 'status' => 'CONFIRMED', 'externalReference' => $order->code,
        ]];

        $this->postJson('/webhooks/payments/asaas', $payload)->assertUnauthorized();
        $this->withHeader('asaas-access-token', 'hook-secret')->postJson('/webhooks/payments/asaas', $payload)->assertOk();
        $this->withHeader('asaas-access-token', 'hook-secret')->postJson('/webhooks/payments/asaas', $payload)->assertOk();

        $this->assertSame(PaymentStatus::Paid, $payment->refresh()->status);
        $this->assertSame('preparing', $order->refresh()->status);
        $this->assertDatabaseCount('webhook_events', 1);
    }

    public function test_asaas_reuses_charge_by_external_reference_after_retry(): void
    {
        $integration = $this->integration('asaas', ['api_key' => 'asaas-secret', 'webhook_token' => 'hook-secret']);
        $created = false;
        Http::fake(function (Request $request) use (&$created) {
            if (str_contains($request->url(), '/v3/customers?')) {
                return Http::response(['data' => [['id' => 'cus-1']]]);
            }
            if (str_contains($request->url(), '/v3/payments?')) {
                return Http::response(['data' => $created ? [[
                    'id' => 'pay-existing', 'status' => 'PENDING', 'invoiceUrl' => 'https://sandbox.asaas.com/i/existing',
                ]] : []]);
            }
            if (str_ends_with($request->url(), '/v3/payments')) {
                $created = true;

                return Http::response([
                    'id' => 'pay-existing', 'status' => 'PENDING', 'invoiceUrl' => 'https://sandbox.asaas.com/i/existing',
                ]);
            }

            return Http::response([], 404);
        });
        $gateway = app(PaymentGatewayManager::class)->for('asaas', $integration);
        $order = $this->order();
        $request = new PaymentRequest($order, $this->payment($order, 'credit_card'), 'credit_card');

        $first = $gateway->createPayment($request);
        $retry = $gateway->createPayment($request);

        $this->assertSame($first->providerPaymentId, $retry->providerPaymentId);
        Http::assertSentCount(5);
        $posts = Http::recorded(fn (Request $sent): bool => $sent->method() === 'POST'
            && str_ends_with($sent->url(), '/v3/payments'));
        $this->assertCount(1, $posts);
    }

    public function test_asaas_failure_is_sanitized(): void
    {
        $integration = $this->integration('asaas', ['api_key' => 'asaas-secret', 'webhook_token' => 'hook-secret']);
        Http::fake(['*' => Http::response(['errors' => [['description' => 'internal provider details']]], 401)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Não foi possível registrar o cliente no Asaas.');

        $order = $this->order();
        app(PaymentGatewayManager::class)->for('asaas', $integration)
            ->createPayment(new PaymentRequest($order, $this->payment($order, 'pix'), 'pix'));
    }

    public function test_mercado_pago_failure_is_sanitized(): void
    {
        $integration = $this->integration('mercado_pago', ['access_token' => 'TEST-secret-token']);
        Http::fake(['api.mercadopago.com/v1/orders' => Http::response([
            'message' => 'sensitive provider diagnostics',
        ], 401)]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Não foi possível criar o pagamento no Mercado Pago.');

        $order = $this->order();
        app(PaymentGatewayManager::class)->for('mercado_pago', $integration)
            ->createPayment(new PaymentRequest($order, $this->payment($order, 'pix'), 'pix'));
    }

    private function integration(string $provider, array $credentials): IntegrationSetting
    {
        return IntegrationSetting::create([
            'type' => 'payment', 'provider' => $provider, 'enabled' => true,
            'environment' => 'sandbox', 'credentials' => $credentials, 'settings' => [],
        ]);
    }

    private function order(): Order
    {
        return Order::create([
            'code' => 'TEST'.str()->random(8), 'status' => 'payment_pending', 'customer_name' => 'Cliente',
            'customer_email' => 'cliente@example.com', 'customer_phone' => '22999990000', 'fulfillment_method' => 'pickup',
            'payment_method' => 'pix', 'payment_provider' => 'mercado_pago', 'payment_status' => 'pending',
            'subtotal_cents' => 10000, 'shipping_cents' => 0, 'discount_cents' => 0, 'total_cents' => 10000,
        ]);
    }

    private function payment(Order $order, string $method): Payment
    {
        return Payment::create([
            'order_id' => $order->id, 'provider' => 'mercado_pago', 'method' => $method,
            'amount_cents' => $order->total_cents, 'status' => 'pending', 'idempotency_key' => (string) str()->uuid(),
        ]);
    }
}
