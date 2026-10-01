<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Filament\Resources\IntegrationSettings\IntegrationSettingResource;
use App\Filament\Resources\IntegrationSettings\Pages\CreateIntegrationSetting;
use App\Filament\Resources\IntegrationSettings\Pages\EditIntegrationSetting;
use App\Models\IntegrationSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Support\Payments\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class IntegrationSecretsTest extends TestCase
{
    use RefreshDatabase;

    public function test_credentials_are_encrypted_and_hidden_from_serialization(): void
    {
        $integration = IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'asaas', 'enabled' => true, 'environment' => 'sandbox',
            'credentials' => ['api_key' => 'super-secret-value', 'webhook_token' => 'webhook-secret-value'],
            'settings' => ['label' => 'Asaas'],
        ]);

        $raw = DB::table('integration_settings')->where('id', $integration->id)->value('credentials');

        $this->assertStringNotContainsString('super-secret-value', $raw);
        $this->assertSame('super-secret-value', $integration->fresh()->credential('api_key'));
        $this->assertArrayNotHasKey('credentials', $integration->fresh()->toArray());
        $this->assertSame('Asaas', $integration->fresh()->setting('label'));
    }

    public function test_empty_secret_fields_preserve_credentials_and_non_secret_edits(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]));
        $integration = IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'mercado_pago', 'enabled' => true,
            'environment' => 'sandbox',
            'credentials' => [
                'access_token' => 'keep-access-token',
                'public_key' => 'keep-public-key',
                'webhook_secret' => 'keep-webhook-secret',
            ],
            'settings' => [],
        ]);

        Livewire::test(EditIntegrationSetting::class, ['record' => $integration->getKey()])
            ->fillForm([
                'type' => 'payment', 'provider' => 'mercado_pago', 'environment' => 'production', 'enabled' => false,
                'credential_public_key' => '', 'credential_access_token' => '', 'credential_webhook_secret' => '',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('keep-access-token', $integration->fresh()->credential('access_token'));
        $this->assertSame('keep-public-key', $integration->fresh()->credential('public_key'));
        $this->assertSame('keep-webhook-secret', $integration->fresh()->credential('webhook_secret'));
        $this->assertSame('production', $integration->fresh()->environment);
        $this->assertFalse($integration->fresh()->enabled);
    }

    public function test_filled_secret_fields_rotate_only_the_submitted_values(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]));
        $integration = IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'mercado_pago', 'enabled' => true,
            'environment' => 'sandbox',
            'credentials' => [
                'access_token' => 'old-access-token',
                'public_key' => 'current-public-key',
                'webhook_secret' => 'old-webhook-secret',
            ],
            'settings' => [],
        ]);

        Livewire::test(EditIntegrationSetting::class, ['record' => $integration->getKey()])
            ->fillForm([
                'type' => 'payment', 'provider' => 'mercado_pago', 'environment' => 'production', 'enabled' => true,
                'credential_public_key' => '', 'credential_access_token' => 'new-access-token', 'credential_webhook_secret' => 'new-webhook-secret',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('new-access-token', $integration->fresh()->credential('access_token'));
        $this->assertSame('current-public-key', $integration->fresh()->credential('public_key'));
        $this->assertSame('new-webhook-secret', $integration->fresh()->credential('webhook_secret'));
    }

    public function test_testing_connection_does_not_enable_integration_or_validate_webhook_secret(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]));
        $integration = IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'mercado_pago', 'enabled' => false,
            'environment' => 'sandbox',
            'credentials' => ['access_token' => 'test-access-token'],
            'settings' => [],
        ]);
        Http::fake(['api.mercadopago.com/users/me' => Http::response(['id' => 1])]);

        Livewire::test(EditIntegrationSetting::class, ['record' => $integration->getKey()])
            ->callAction('test');

        $this->assertFalse($integration->fresh()->enabled);
        $this->assertSame('connected', $integration->fresh()->last_test_status);
        $this->assertNotNull($integration->fresh()->connected_at);
        $this->assertNotNull($integration->fresh()->last_tested_at);
        Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/users/me'));
    }

    public function test_delete_action_is_confirmed_and_allows_recreation_without_payment_history(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]));
        config(['services.mercado_pago.access_token' => 'legacy-access-token', 'services.mercado_pago.public_key' => 'legacy-public-key']);
        $integration = IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'mercado_pago', 'enabled' => true,
            'environment' => 'sandbox', 'credentials' => ['access_token' => 'old-token', 'public_key' => 'old-key'], 'settings' => [],
        ]);

        Livewire::test(EditIntegrationSetting::class, ['record' => $integration->getKey()])
            ->assertActionExists('deleteIntegration')
            ->assertActionEnabled('deleteIntegration')
            ->callAction('deleteIntegration');

        $this->assertDatabaseMissing('integration_settings', ['id' => $integration->id]);
        $this->assertNull(app(PaymentGatewayManager::class)->active());
        Livewire::test(CreateIntegrationSetting::class)
            ->fillForm([
                'type' => 'payment', 'provider' => 'mercado_pago', 'environment' => 'production', 'enabled' => true,
                'credential_public_key' => 'replacement-public-key',
                'credential_access_token' => 'replacement-token',
                'credential_webhook_secret' => 'replacement-webhook-secret',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $replacement = IntegrationSetting::query()->where('provider', 'mercado_pago')->firstOrFail();
        $this->assertSame('replacement-token', $replacement->credential('access_token'));
        $this->assertSame('replacement-public-key', $replacement->credential('public_key'));
        $this->assertSame('replacement-webhook-secret', $replacement->credential('webhook_secret'));
        $this->assertSame($replacement->id, app(PaymentGatewayManager::class)->activeIntegration()?->id);
    }

    public function test_delete_is_blocked_when_provider_has_historical_payments(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]));
        $integration = IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'mercado_pago', 'enabled' => false,
            'environment' => 'production', 'credentials' => ['access_token' => 'old-token', 'webhook_secret' => 'old-secret'], 'settings' => [],
        ]);
        $order = Order::create([
            'code' => 'HISTORIC-MP-1', 'status' => 'payment_pending', 'customer_name' => 'Cliente',
            'customer_email' => 'cliente@example.com', 'customer_phone' => '22999990000',
            'fulfillment_method' => 'pickup', 'payment_method' => 'pix', 'payment_provider' => 'mercado_pago',
            'subtotal_cents' => 1000, 'shipping_cents' => 0, 'discount_cents' => 0, 'total_cents' => 1000,
            'mercado_pago_payment_id' => '123456',
        ]);
        $payment = Payment::create([
            'order_id' => $order->id, 'provider' => 'mercado_pago', 'provider_payment_id' => '123456',
            'method' => 'pix', 'amount_cents' => 1000, 'status' => 'pending',
            'idempotency_key' => 'historic-payment-attempt',
        ]);

        Livewire::test(EditIntegrationSetting::class, ['record' => $integration->getKey()])
            ->assertActionDisabled('deleteIntegration');

        $this->assertDatabaseHas('integration_settings', ['id' => $integration->id]);
        $this->assertSame('old-secret', $integration->fresh()->credential('webhook_secret'));

        Http::fake(['api.mercadopago.com/v1/payments/123456' => Http::response([
            'id' => 123456, 'external_reference' => $order->code,
            'status' => 'approved', 'status_detail' => 'accredited',
        ])]);
        $requestId = 'historic-hook-request';
        $timestamp = (string) now()->timestamp;
        $manifest = 'id:123456;request-id:'.$requestId.';ts:'.$timestamp.';';
        $signature = hash_hmac('sha256', $manifest, 'old-secret');
        $this->withHeaders([
            'x-signature' => 'ts='.$timestamp.',v1='.$signature,
            'x-request-id' => $requestId,
        ])->postJson('/webhooks/payments/mercado-pago?type=payment&data.id=123456', [
            'type' => 'payment', 'data' => ['id' => '123456'],
        ])->assertOk();

        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
    }

    public function test_duplicate_provider_for_same_type_is_rejected_in_filament_form(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]));
        IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'mercado_pago', 'enabled' => false,
            'environment' => 'sandbox', 'credentials' => [], 'settings' => [],
        ]);

        Livewire::test(CreateIntegrationSetting::class)
            ->fillForm([
                'type' => 'payment', 'provider' => 'mercado_pago', 'environment' => 'sandbox', 'enabled' => false,
                'credential_access_token' => 'new-access-token', 'credential_public_key' => 'new-public-key',
                'credential_webhook_secret' => 'new-webhook-secret',
            ])
            ->call('create')
            ->assertHasFormErrors(['provider' => 'unique']);

        $this->assertDatabaseCount('integration_settings', 1);
    }

    public function test_mercado_pago_secret_status_and_webhook_labels_are_clear_without_rendering_values(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $integration = IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'mercado_pago', 'enabled' => true,
            'environment' => 'production',
            'credentials' => [
                'access_token' => 'do-not-render-access-token',
                'public_key' => 'do-not-render-public-key',
                'webhook_secret' => 'do-not-render-webhook-secret',
            ],
            'settings' => [],
        ]);

        $this->actingAs($admin)
            ->get(IntegrationSettingResource::getUrl('edit', ['record' => $integration]))
            ->assertOk()
            ->assertSee('Configurado')
            ->assertSee('Deixe em branco para manter o Access Token atual.')
            ->assertSee('Nova assinatura secreta do Webhook')
            ->assertSee('Deixe em branco para manter a assinatura atual.')
            ->assertSee('URL do Webhook')
            ->assertSee('Cadastre esta URL na configuração de Webhooks da aplicação no Mercado Pago.')
            ->assertSee('Não valida webhook_secret nem ativa a integração.')
            ->assertDontSee('do-not-render-access-token')
            ->assertDontSee('do-not-render-public-key')
            ->assertDontSee('do-not-render-webhook-secret');
    }

    public function test_integration_list_shows_activation_configuration_and_connection_test_states(): void
    {
        IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'mercado_pago', 'enabled' => true,
            'environment' => 'production',
            'credentials' => ['access_token' => 'token', 'public_key' => 'key', 'webhook_secret' => 'secret'],
            'connected_at' => now(), 'last_tested_at' => now(), 'last_test_status' => 'connected', 'settings' => [],
        ]);
        IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'asaas', 'enabled' => false,
            'environment' => 'production', 'credentials' => [],
            'last_tested_at' => now(), 'last_test_status' => 'failed', 'settings' => [],
        ]);
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]));

        $this->get(IntegrationSettingResource::getUrl('index'))
            ->assertOk()
            ->assertSee('Ativa')
            ->assertSee('Desativada')
            ->assertSee('Configurada')
            ->assertSee('Não configurada')
            ->assertSee('Conexão testada')
            ->assertSee('Teste falhou');
    }

    public function test_existing_secret_is_not_repopulated_in_filament_html(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'is_active' => true]);
        $integration = IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'asaas', 'enabled' => true, 'environment' => 'sandbox',
            'credentials' => ['api_key' => 'never-render-this-secret', 'webhook_token' => 'another-secret'],
            'settings' => [],
        ]);

        $this->actingAs($admin)
            ->get(IntegrationSettingResource::getUrl('edit', ['record' => $integration]))
            ->assertOk()
            ->assertDontSee('never-render-this-secret')
            ->assertDontSee('another-secret')
            ->assertSee('Configurada')
            ->assertSee('Deixe em branco para manter a API key atual.');
    }
}
