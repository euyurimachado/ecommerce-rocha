<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\IntegrationSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Payments\Asaas\AsaasGateway;
use App\Support\Payments\MercadoPago\MercadoPagoGateway;
use App\Support\Payments\PaymentGatewayManager;
use App\Support\Payments\PaymentRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class PaymentGatewaysTest extends TestCase
{
    use RefreshDatabase;

    public function test_mercado_pago_creates_pix_and_tokenized_card_through_payments_api(): void
    {
        $integration = $this->integration('mercado_pago', ['access_token' => 'TEST-token', 'public_key' => 'TEST-public']);
        $order = $this->order();
        $order->forceFill(['customer_tax_id' => '12345678909'])->save();
        Http::fake(['api.mercadopago.com/v1/payments' => Http::sequence()
            ->push([
                'id' => 501, 'status' => 'pending', 'status_detail' => 'pending_waiting_payment',
                'point_of_interaction' => ['transaction_data' => [
                    'qr_code' => 'pix-code', 'qr_code_base64' => 'base64-image', 'ticket_url' => 'https://www.mercadopago.com.br/ticket/501',
                ]],
            ], 201)
            ->push(['id' => 502, 'status' => 'approved', 'status_detail' => 'accredited'], 201)]);
        URL::forceRootUrl('https://shop.example.test');
        URL::forceScheme('https');
        $gateway = app(PaymentGatewayManager::class)->for('mercado_pago', $integration);
        $pixPayment = $this->payment($order, 'pix');
        $pix = $gateway->createPayment(new PaymentRequest($order, $pixPayment, 'pix'));
        $cardPayment = $this->payment($order, 'credit_card');
        $card = $gateway->createPayment(new PaymentRequest($order, $cardPayment, 'credit_card', 'secure-card-token', 'master', 3, '123', 'CPF', '12345678909'));

        $this->assertSame('501', $pix->providerPaymentId);
        $this->assertSame(PaymentStatus::Pending, $pix->status);
        $this->assertSame('pending_waiting_payment', $pix->metadata['status_detail']);
        $this->assertSame('pix-code', $pix->pixCode);
        $this->assertSame('base64-image', $pix->pixQrCodeBase64);
        $this->assertSame('https://www.mercadopago.com.br/ticket/501', $pix->pixTicketUrl);
        $this->assertSame('payment_pending', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(PaymentStatus::Paid, $card->status);

        $posts = Http::recorded(fn (Request $sent): bool => $sent->method() === 'POST');
        $this->assertCount(2, $posts);
        Http::assertSent(fn (Request $sent): bool => $sent->url() === 'https://api.mercadopago.com/v1/payments'
            && $sent->header('X-Idempotency-Key')[0] === $pixPayment->idempotency_key
            && $sent['payment_method_id'] === 'pix'
            && $sent['transaction_amount'] === 100.0
            && $sent['external_reference'] === $order->code
            && $sent['payer']['email'] === $order->customer_email
            && $sent['payer']['first_name'] === 'Cliente'
            && $sent['payer']['identification'] === ['type' => 'CPF', 'number' => '12345678909']
            && $sent['notification_url'] === 'https://shop.example.test/webhooks/payments/mercado-pago'
            && ! isset($sent['processing_mode'], $sent['total_amount'], $sent['transactions']));
        Http::assertSent(fn (Request $sent): bool => $sent->url() === 'https://api.mercadopago.com/v1/payments'
            && isset($sent['token'], $sent['issuer_id'])
            && $sent['token'] === 'secure-card-token'
            && $sent['installments'] === 3
            && $sent['payment_method_id'] === 'master'
            && $sent['issuer_id'] === 123
            && $sent['notification_url'] === 'https://shop.example.test/webhooks/payments/mercado-pago'
            && ! isset($sent['card_number'], $sent['cvv']));
        Http::assertNotSent(fn (Request $sent): bool => str_ends_with($sent->url(), '/v1/orders'));
    }

    public function test_mercado_pago_retry_reuses_persisted_payment_without_second_post(): void
    {
        $integration = $this->integration('mercado_pago', ['access_token' => 'TEST-token']);
        $order = $this->order();
        $payment = $this->payment($order, 'pix');
        Http::fake(function (Request $request) {
            return Http::response(['id' => 811, 'status' => 'pending'], 201);
        });
        $gateway = app(PaymentGatewayManager::class)->for('mercado_pago', $integration);
        $request = new PaymentRequest($order, $payment, 'pix');

        $created = $gateway->createPayment($request);
        $payment->update(['provider_payment_id' => $created->providerPaymentId]);
        $retry = $gateway->createPayment($request);

        $this->assertSame($created->providerPaymentId, $retry->providerPaymentId);
        Http::assertSentCount(2);
        $this->assertCount(1, Http::recorded(fn (Request $sent): bool => $sent->method() === 'POST'));
        $this->assertCount(1, Http::recorded(fn (Request $sent): bool => $sent->method() === 'GET'));
    }

    public function test_uncertain_payment_reconciles_by_external_reference_before_retrying_post(): void
    {
        $integration = $this->integration('mercado_pago', ['access_token' => 'TEST-token']);
        $order = $this->order();
        $order->forceFill(['payment_method' => 'credit_card'])->save();
        $payment = $this->payment($order, 'credit_card');
        Http::fake(function (Request $request) use ($order) {
            if ($request->method() === 'GET' && parse_url($request->url(), PHP_URL_PATH) === '/v1/payments/search') {
                $this->assertSame($order->code, $request['external_reference']);

                return Http::response(['results' => [[
                    'id' => 'reconciled-payment',
                    'status' => 'approved',
                    'status_detail' => 'accredited',
                    'external_reference' => $order->code,
                    'transaction_amount' => 100.0,
                    'payment_type_id' => 'credit_card',
                    'payment_method_id' => 'visa',
                ]]], 200);
            }

            return Http::response([], 500);
        });

        $result = app(PaymentGatewayManager::class)->for('mercado_pago', $integration)->createPayment(
            new PaymentRequest($order, $payment, 'credit_card', 'fresh-token', 'visa', 1, reconcileFirst: true),
        );

        $this->assertSame('reconciled-payment', $result->providerPaymentId);
        $this->assertSame(PaymentStatus::Paid, $result->status);
        $this->assertCount(0, Http::recorded(fn (Request $request): bool => $request->method() === 'POST'));
    }

    public function test_uncertain_retry_without_search_match_posts_using_the_original_idempotency_key(): void
    {
        $integration = $this->integration('mercado_pago', ['access_token' => 'TEST-token']);
        $order = $this->order();
        $payment = $this->payment($order, 'credit_card');
        $originalKey = $payment->idempotency_key;
        Http::fake(function (Request $request) {
            if ($request->method() === 'GET' && parse_url($request->url(), PHP_URL_PATH) === '/v1/payments/search') {
                return Http::response(['results' => []], 200);
            }

            if ($request->method() === 'POST' && str_ends_with($request->url(), '/v1/payments')) {
                return Http::response(['id' => 'retried-payment', 'status' => 'pending'], 201);
            }

            return Http::response([], 404);
        });

        $result = app(PaymentGatewayManager::class)->for('mercado_pago', $integration)->createPayment(
            new PaymentRequest($order, $payment, 'credit_card', 'new-transient-token', 'visa', 2, reconcileFirst: true),
        );

        $this->assertSame('retried-payment', $result->providerPaymentId);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->header('X-Idempotency-Key')[0] === $originalKey);
        $this->assertCount(1, Http::recorded(fn (Request $request): bool => $request->method() === 'POST'));
    }

    public function test_ambiguous_search_results_block_retry_instead_of_creating_another_payment(): void
    {
        $integration = $this->integration('mercado_pago', ['access_token' => 'TEST-token']);
        $order = $this->order();
        $payment = $this->payment($order, 'pix');
        $candidate = [
            'id' => 'ambiguous-payment', 'status' => 'pending', 'external_reference' => $order->code,
            'transaction_amount' => 100.0, 'payment_method_id' => 'pix',
        ];
        Http::fake(function (Request $request) use ($candidate) {
            return $request->method() === 'GET'
                ? Http::response(['results' => [$candidate, $candidate + ['id' => 'another-payment']]], 200)
                : Http::response([], 500);
        });

        $this->expectException(RuntimeException::class);
        app(PaymentGatewayManager::class)->for('mercado_pago', $integration)->createPayment(
            new PaymentRequest($order, $payment, 'pix', reconcileFirst: true),
        );

        $this->assertCount(0, Http::recorded(fn (Request $request): bool => $request->method() === 'POST'));
    }

    public function test_successful_payment_does_not_write_temporary_diagnostic_logs(): void
    {
        $integration = $this->integration('mercado_pago', ['access_token' => 'TEST-token']);
        $order = $this->order();
        $payment = $this->payment($order, 'credit_card');
        Http::fake(['api.mercadopago.com/v1/payments' => Http::response([
            'id' => 'safe-payment-id', 'status' => 'approved', 'status_detail' => 'accredited',
        ], 201)]);
        Log::shouldReceive('info')->never();

        app(PaymentGatewayManager::class)->for('mercado_pago', $integration)->createPayment(
            new PaymentRequest($order, $payment, 'credit_card', 'fake-card-token', 'visa', 1),
        );
    }

    public function test_mercado_pago_cancel_and_refund_use_payment_api(): void
    {
        $integration = $this->integration('mercado_pago', ['access_token' => 'TEST-token']);
        Http::fake(function (Request $request) {
            if ($request->method() === 'PUT') {
                return Http::response(['id' => 812, 'status' => 'cancelled'], 200);
            }
            if ($request->method() === 'POST' && str_ends_with($request->url(), '/refunds')) {
                return Http::response(['id' => 91, 'status' => 'approved'], 201);
            }

            return Http::response(['id' => 812, 'status' => 'refunded', 'status_detail' => 'refunded'], 200);
        });
        $gateway = app(PaymentGatewayManager::class)->for('mercado_pago', $integration);

        $cancelled = $gateway->cancel('812');
        $refunded = $gateway->refund('812', 2500);

        $this->assertSame(PaymentStatus::Cancelled, $cancelled->status);
        $this->assertSame(PaymentStatus::Refunded, $refunded->status);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
            && str_ends_with($request->url(), '/v1/payments/812')
            && $request['status'] === 'cancelled');
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_ends_with($request->url(), '/v1/payments/812/refunds')
            && $request['amount'] == 25.0
            && filled($request->header('X-Idempotency-Key')[0] ?? null));
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_ends_with($request->url(), '/v1/payments/812'));
    }

    public static function mercadoPagoStatuses(): array
    {
        return [
            'approved' => ['approved', PaymentStatus::Paid],
            'pending' => ['pending', PaymentStatus::Pending],
            'authorized' => ['authorized', PaymentStatus::Processing],
            'in process' => ['in_process', PaymentStatus::Processing],
            'rejected' => ['rejected', PaymentStatus::Failed],
            'cancelled' => ['cancelled', PaymentStatus::Cancelled],
            'refunded' => ['refunded', PaymentStatus::Refunded],
            'charged back' => ['charged_back', PaymentStatus::Refunded],
        ];
    }

    #[DataProvider('mercadoPagoStatuses')]
    public function test_mercado_pago_normalizes_payments_api_statuses(string $status, PaymentStatus $expected): void
    {
        $integration = $this->integration('mercado_pago', ['access_token' => 'TEST-token']);
        $order = $this->order();
        Http::fake(['api.mercadopago.com/v1/payments' => Http::response(['id' => 700, 'status' => $status, 'status_detail' => 'test_detail'], 201)]);

        $result = app(PaymentGatewayManager::class)->for('mercado_pago', $integration)
            ->createPayment(new PaymentRequest($order, $this->payment($order, 'pix'), 'pix'));

        $this->assertSame($expected, $result->status);
        $this->assertSame('test_detail', $result->metadata['status_detail']);
    }

    public function test_mercado_pago_rejects_card_without_token_before_api_call(): void
    {
        $integration = $this->integration('mercado_pago', ['access_token' => 'TEST-token']);
        $order = $this->order();
        Http::fake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Não foi possível validar o cartão. Confira os dados e tente novamente.');
        app(PaymentGatewayManager::class)->for('mercado_pago', $integration)
            ->createPayment(new PaymentRequest($order, $this->payment($order, 'credit_card'), 'credit_card'));
    }

    public static function mercadoPagoHttpErrors(): array
    {
        return ['400' => [400], '401' => [401], '403' => [403], '422' => [422], '500' => [500]];
    }

    #[DataProvider('mercadoPagoHttpErrors')]
    public function test_mercado_pago_http_errors_are_logged_without_secrets(int $status): void
    {
        $integration = $this->integration('mercado_pago', ['access_token' => 'TEST-secret-token']);
        $order = $this->order();
        $payment = $this->payment($order, 'pix');
        Http::fake(['api.mercadopago.com/v1/payments' => Http::response([
            'error' => 'request_rejected', 'message' => 'Authorization: Bearer TEST-response-secret',
            'cause' => [['code' => 'policy_denied', 'description' => 'buyer@example.com CPF 12345678909']],
        ], $status)]);
        Log::shouldReceive('warning')->once()->withArgs(function (string $message, array $context) use ($status, $order, $payment): bool {
            $json = json_encode($context);

            return $message === 'Mercado Pago payment creation failed.'
                && $context['http_status'] === $status
                && $context['order_id'] === $order->id
                && $context['payment_id'] === $payment->id
                && $context['endpoint'] === '/v1/payments'
                && ! str_contains($json, 'TEST-secret-token')
                && ! str_contains($json, 'TEST-response-secret')
                && ! str_contains($json, 'buyer@example.com')
                && ! str_contains($json, '12345678909');
        });

        try {
            app(PaymentGatewayManager::class)->for('mercado_pago', $integration)
                ->createPayment(new PaymentRequest($order, $payment, 'pix'));
            $this->fail('Expected Mercado Pago payment creation to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Não foi possível criar o pagamento no Mercado Pago.', $exception->getMessage());
        }
    }

    public function test_mercado_pago_logs_sanitized_order_failure_context(): void
    {
        $integration = $this->integration('mercado_pago', ['access_token' => 'TEST-secret-token']);
        $order = $this->order();
        $payment = $this->payment($order, 'pix');
        Http::fake(['api.mercadopago.com/v1/payments' => Http::response([
            'error' => 'bad_request',
            'cause' => [['code' => 'missing_field', 'description' => 'processing_mode is required']],
            'message' => 'Invalid request for buyer@example.com; card=4111111111111111; cvv=123; access_token=TEST-message-token; Authorization: Bearer TEST-bearer-token; card_token=TEST-card-token',
        ], 400, ['x-request-id' => 'provider-request-123'])]);

        Log::shouldReceive('warning')->once()->withArgs(function (string $message, array $context) use ($order, $payment): bool {
            return $message === 'Mercado Pago payment creation failed.'
                && $context['provider'] === 'mercado_pago'
                && $context['order_id'] === $order->id
                && $context['payment_id'] === $payment->id
                && $context['endpoint'] === '/v1/payments'
                && $context['http_status'] === 400
                && $context['provider_error_code'] === 'bad_request'
                && $context['provider_causes'][0]['code'] === 'missing_field'
                && $context['provider_message'] === 'Invalid request for [redacted-email]; card=[redacted-number]; cvv=[REDACTED]; access_token=[REDACTED]; Authorization=[REDACTED]; card_token=[REDACTED]'
                && $context['provider_request_id'] === 'provider-request-123'
                && ! str_contains(json_encode($context), 'TEST-secret-token')
                && ! str_contains(json_encode($context), 'TEST-message-token')
                && ! str_contains(json_encode($context), 'TEST-bearer-token')
                && ! str_contains(json_encode($context), 'TEST-card-token')
                && ! str_contains(json_encode($context), '4111111111111111');
        });

        try {
            app(PaymentGatewayManager::class)->for('mercado_pago', $integration)
                ->createPayment(new PaymentRequest($order, $payment, 'pix'));
            $this->fail('Expected Mercado Pago order creation to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Não foi possível criar o pagamento no Mercado Pago.', $exception->getMessage());
        }
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

    public function test_payment_gateway_manager_has_no_runtime_fallback_without_persisted_settings(): void
    {
        config([
            'services.mercado_pago.access_token' => 'legacy-access-token',
            'services.mercado_pago.public_key' => 'legacy-public-key',
        ]);

        $manager = app(PaymentGatewayManager::class);

        $this->assertNull($manager->active());
        $this->assertNull($manager->provider());
        $this->assertNull($manager->publicKey());
        $this->assertDatabaseCount('integration_settings', 0);
    }

    public function test_payment_gateway_manager_ignores_fallback_when_persisted_mercado_pago_is_disabled(): void
    {
        config([
            'services.mercado_pago.access_token' => 'legacy-access-token',
            'services.mercado_pago.public_key' => 'legacy-public-key',
        ]);
        IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'mercado_pago', 'enabled' => false,
            'environment' => 'production', 'credentials' => [], 'settings' => [],
        ]);

        $manager = app(PaymentGatewayManager::class);

        $this->assertNull($manager->active());
        $this->assertNull($manager->provider());
        $this->assertNull($manager->publicKey());
    }

    public function test_payment_gateway_manager_uses_active_persisted_mercado_pago(): void
    {
        config([
            'services.mercado_pago.access_token' => 'legacy-access-token',
            'services.mercado_pago.public_key' => 'legacy-public-key',
        ]);
        $integration = $this->integration('mercado_pago', [
            'access_token' => 'database-access-token', 'public_key' => 'database-public-key',
        ]);

        $manager = app(PaymentGatewayManager::class);

        $this->assertInstanceOf(MercadoPagoGateway::class, $manager->active());
        $this->assertSame($integration->id, $manager->activeIntegration()?->id);
        $this->assertSame('database-public-key', $manager->publicKey());
    }

    public function test_payment_gateway_manager_selects_active_asaas(): void
    {
        config(['services.mercado_pago.public_key' => 'legacy-public-key']);
        $integration = $this->integration('asaas', ['api_key' => 'asaas-api-key']);

        $manager = app(PaymentGatewayManager::class);

        $this->assertInstanceOf(AsaasGateway::class, $manager->active());
        $this->assertSame($integration->id, $manager->activeIntegration()?->id);
        $this->assertSame('asaas', $manager->provider());
        $this->assertNull($manager->publicKey());
    }

    public function test_payment_gateway_manager_returns_no_gateway_when_all_settings_are_disabled(): void
    {
        config(['services.mercado_pago.access_token' => 'legacy-access-token']);
        IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'mercado_pago', 'enabled' => false,
            'environment' => 'production', 'credentials' => [], 'settings' => [],
        ]);
        IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'asaas', 'enabled' => false,
            'environment' => 'production', 'credentials' => [], 'settings' => [],
        ]);

        $manager = app(PaymentGatewayManager::class);

        $this->assertNull($manager->active());
        $this->assertNull($manager->provider());
    }

    public function test_payment_gateway_manager_disconnect_removes_mercado_pago_from_checkout(): void
    {
        config(['services.mercado_pago.public_key' => 'legacy-public-key']);
        $integration = $this->integration('mercado_pago', [
            'access_token' => 'database-access-token', 'public_key' => 'database-public-key',
        ]);
        $integration->update([
            'enabled' => false, 'credentials' => [], 'connected_at' => null,
            'last_test_status' => 'disconnected',
        ]);

        $manager = app(PaymentGatewayManager::class);

        $this->assertNull($manager->active());
        $this->assertNull($manager->publicKey());
    }

    public function test_payment_gateway_manager_delete_does_not_reactivate_environment_and_allows_recreation(): void
    {
        config([
            'services.mercado_pago.access_token' => 'legacy-access-token',
            'services.mercado_pago.public_key' => 'legacy-public-key',
        ]);
        $integration = $this->integration('mercado_pago', [
            'access_token' => 'database-access-token', 'public_key' => 'database-public-key',
        ]);
        $integration->delete();

        $manager = app(PaymentGatewayManager::class);
        $this->assertNull($manager->active());
        $this->assertNull($manager->publicKey());

        $replacement = $this->integration('mercado_pago', [
            'access_token' => 'replacement-token', 'public_key' => 'replacement-public-key',
        ]);
        $this->assertSame($replacement->id, $manager->activeIntegration()?->id);
    }

    public function test_payment_gateway_manager_keeps_disabled_provider_unavailable_after_delete_with_legacy_credentials(): void
    {
        config([
            'services.mercado_pago.access_token' => 'legacy-access-token',
            'services.mercado_pago.public_key' => 'legacy-public-key',
        ]);
        $integration = IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'mercado_pago', 'enabled' => false,
            'environment' => 'production', 'credentials' => [], 'settings' => [],
        ]);
        $integration->delete();

        $manager = app(PaymentGatewayManager::class);
        $this->assertNull($manager->active());
        $this->assertNull($manager->provider());
        $this->assertNull($manager->publicKey());
    }

    public function test_payment_gateway_manager_webhook_resolves_disabled_setting_and_rejects_missing_secret(): void
    {
        $this->app['env'] = 'production';
        IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'mercado_pago', 'enabled' => false,
            'environment' => 'production', 'credentials' => [], 'settings' => [],
        ]);

        $this->postJson('/webhooks/payments/mercado-pago', ['type' => 'payment', 'data' => ['id' => 123]])
            ->assertUnauthorized();

        $this->assertDatabaseCount('webhook_events', 0);
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
