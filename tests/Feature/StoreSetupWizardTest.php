<?php

namespace Tests\Feature;

use App\Filament\Pages\StoreSetupWizard;
use App\Models\IntegrationSetting;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class StoreSetupWizardTest extends TestCase
{
    use RefreshDatabase;

    private StoreSetting $store;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = StoreSetting::query()->create([
            'name' => 'Loja Original', 'short_name' => 'Original', 'legal_name' => 'Loja Original LTDA',
            'slogan' => 'Slogan original', 'primary_color' => '#123456', 'primary_dark_color' => '#102030',
            'secondary_color' => '#abcdef', 'accent_color' => '#fedcba', 'background_color' => '#f8fafc',
            'font_family' => 'Inter', 'tax_id' => '12345678000199', 'email' => 'contato@loja.test',
            'phone' => '22999990000', 'postal_code' => '28000000', 'street' => 'Rua Teste', 'number' => '10',
            'neighborhood' => 'Centro', 'city' => 'Campos dos Goytacazes', 'state' => 'RJ', 'country' => 'BR',
            'installed_at' => now()->subMonth(), 'installation_version' => '1.0.0',
        ]);
        IntegrationSetting::query()->create([
            'type' => 'payment', 'provider' => 'mercado_pago', 'enabled' => true, 'environment' => 'sandbox',
            'credentials' => ['public_key' => 'TEST-public', 'access_token' => 'old-access-token', 'webhook_secret' => 'old-webhook-secret-strong'],
            'settings' => [],
        ]);
        IntegrationSetting::query()->create([
            'type' => 'shipping', 'provider' => 'flat_rate', 'enabled' => true, 'environment' => 'production',
            'credentials' => [], 'settings' => ['name' => 'Entrega local', 'price_cents' => 990, 'min_days' => 1, 'max_days' => 2],
        ]);
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
    }

    public function test_wizard_is_admin_only_and_public_installer_stays_blocked(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $this->actingAs($this->admin)->get(StoreSetupWizard::getUrl())->assertOk()->assertSee('Assistente de reconfiguração');
        $this->actingAs($manager)->get(StoreSetupWizard::getUrl())->assertForbidden();
        $this->get('/install?token=anything')->assertNotFound();
    }

    public function test_legacy_installation_with_admin_can_open_reconfiguration_without_store_settings(): void
    {
        $this->store->delete();
        config(['installer.token' => 'legacy-token']);

        $this->actingAs($this->admin)
            ->get(StoreSetupWizard::getUrl())
            ->assertOk()
            ->assertSee('Assistente de reconfiguração');

        $this->get('/install?token=legacy-token')->assertNotFound();
    }

    public function test_reconfiguration_marks_a_legacy_store_without_changing_existing_install_dates(): void
    {
        Http::fake(['api.mercadopago.com/users/me' => Http::response(['id' => 123], 200)]);
        $this->store->update(['installed_at' => null, 'installation_version' => null]);
        $this->actingAs($this->admin);

        Livewire::test(StoreSetupWizard::class)
            ->call('finish')
            ->assertHasNoErrors();

        $this->assertNotNull($this->store->fresh()->installed_at);
        $this->assertSame(config('installer.version'), $this->store->fresh()->installation_version);
    }

    public function test_current_values_are_prefilled_but_existing_secrets_are_not_hydrated(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(StoreSetupWizard::class)
            ->assertSet('store.name', 'Loja Original')
            ->assertSet('company.city', 'Campos dos Goytacazes')
            ->assertSet('payment.provider', 'mercado_pago')
            ->assertSet('payment.access_token', '')
            ->assertSet('payment.webhook_secret', '')
            ->assertSet('configuredCredentials.payment', true);
    }

    public function test_finish_updates_settings_preserves_blank_secrets_and_does_not_create_admin(): void
    {
        Http::fake(['api.mercadopago.com/users/me' => Http::response(['id' => 123], 200)]);
        $installedAt = $this->store->installed_at->toISOString();
        $userCount = User::query()->count();
        $this->actingAs($this->admin);

        Livewire::test(StoreSetupWizard::class)
            ->set('store.name', 'Loja Atualizada')
            ->set('company.phone', '22988887777')
            ->call('finish')
            ->assertHasNoErrors();

        $this->store->refresh();
        $payment = IntegrationSetting::active('payment');
        $this->assertSame('Loja Atualizada', $this->store->name);
        $this->assertSame('22988887777', $this->store->phone);
        $this->assertSame($installedAt, $this->store->installed_at->toISOString());
        $this->assertSame('old-access-token', $payment?->credential('access_token'));
        $this->assertSame($userCount, User::query()->count());
    }

    public function test_a_new_secret_rotates_only_that_secret(): void
    {
        Http::fake(['api.mercadopago.com/users/me' => Http::response(['id' => 123], 200)]);
        $this->actingAs($this->admin);

        Livewire::test(StoreSetupWizard::class)
            ->set('payment.access_token', 'new-access-token')
            ->call('finish')
            ->assertHasNoErrors();

        $payment = IntegrationSetting::active('payment');
        $this->assertSame('new-access-token', $payment?->credential('access_token'));
        $this->assertSame('old-webhook-secret-strong', $payment?->credential('webhook_secret'));
    }

    public function test_cancel_discards_draft_without_persistence(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(StoreSetupWizard::class)
            ->set('store.name', 'Não persistir')
            ->call('cancel')
            ->assertRedirect('/admin');

        $this->assertSame('Loja Original', $this->store->fresh()->name);
    }

    public function test_invalid_payment_provider_never_replaces_the_active_provider(): void
    {
        IntegrationSetting::query()->create([
            'type' => 'payment', 'provider' => 'asaas', 'enabled' => false, 'environment' => 'sandbox',
            'credentials' => ['api_key' => 'invalid-key', 'webhook_token' => 'invalid-webhook-token'], 'settings' => [],
        ]);
        Http::fake([
            'sandbox.asaas.com/api/v3/myAccount' => Http::response(['errors' => []], 401),
            'api.mercadopago.com/users/me' => Http::response(['id' => 123], 200),
        ]);
        $this->actingAs($this->admin);

        Livewire::test(StoreSetupWizard::class)
            ->set('payment.provider', 'asaas')
            ->call('finish')
            ->assertHasErrors(['payment.provider']);

        $this->assertSame('mercado_pago', IntegrationSetting::active('payment')?->provider);
    }

    public function test_invalid_shipping_provider_never_replaces_the_active_provider(): void
    {
        IntegrationSetting::query()->create([
            'type' => 'shipping', 'provider' => 'melhor_envio', 'enabled' => false, 'environment' => 'sandbox',
            'credentials' => [
                'client_id' => 'client', 'client_secret' => 'secret', 'access_token' => 'invalid-token',
                'expires_at' => now()->addHour()->toIso8601String(),
            ],
            'settings' => [],
        ]);
        Http::fake([
            'sandbox.melhorenvio.com.br/api/v2/me' => Http::response([], 401),
            'api.mercadopago.com/users/me' => Http::response(['id' => 123], 200),
        ]);
        $this->actingAs($this->admin);

        Livewire::test(StoreSetupWizard::class)
            ->set('shipping.provider', 'melhor_envio')
            ->call('finish')
            ->assertHasErrors(['shipping.provider']);

        $this->assertSame('flat_rate', IntegrationSetting::active('shipping')?->provider);
    }
}
