<?php

namespace Tests\Feature;

use App\Models\IntegrationSetting;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['installer.token' => 'a-strong-installer-token']);
    }

    public function test_installer_requires_token_and_is_available_before_installation(): void
    {
        $this->get('/install?token=wrong')->assertForbidden();
        $this->get('/install?token=a-strong-installer-token')->assertOk()->assertSee('Criar administrador');
    }

    public function test_installer_creates_admin_persists_settings_and_locks_itself(): void
    {
        Storage::fake('public');
        Http::fake(['api.mercadopago.com/users/me' => Http::response(['id' => 123], 200)]);
        $session = ['installer_authorized' => true];

        $this->withSession($session)->post('/install/1', [
            'name' => 'Admin Loja', 'email' => 'admin@loja.test',
            'password' => 'UmaSenhaMuitoForte!1', 'password_confirmation' => 'UmaSenhaMuitoForte!1',
        ])->assertRedirect('/install/2');
        $this->post('/install/2', [
            'name' => 'Loja Teste', 'short_name' => 'Teste', 'legal_name' => 'Loja Teste LTDA', 'slogan' => 'Tudo para você',
            'primary_color' => '#123456', 'primary_dark_color' => '#102030', 'secondary_color' => '#abcdef',
            'accent_color' => '#fedcba', 'background_color' => '#f8fafc', 'font_family' => 'Inter',
            'logo' => UploadedFile::fake()->image('logo.png', 320, 120),
        ])->assertRedirect('/install/3');
        $this->post('/install/3', [
            'tax_id' => '12345678000199', 'email' => 'contato@loja.test', 'phone' => '22999990000',
            'postal_code' => '28000000', 'street' => 'Rua Teste', 'number' => '10', 'neighborhood' => 'Centro',
            'city' => 'Campos dos Goytacazes', 'state' => 'RJ', 'country' => 'BR',
        ])->assertRedirect('/install/4');
        $this->post('/install/4', [
            'provider' => 'mercado_pago', 'environment' => 'sandbox', 'public_key' => 'TEST-public',
            'access_token' => 'TEST-secret-access-token', 'webhook_secret' => 'webhook-secret-strong',
        ])->assertRedirect('/install/5');
        $this->post('/install/5', [
            'provider' => 'flat_rate', 'environment' => 'production', 'name' => 'Entrega local',
            'price_cents' => 990, 'min_days' => 1, 'max_days' => 2, 'free_shipping_threshold_cents' => 25000,
        ])->assertRedirect('/install/6');
        $this->post('/install/6')->assertRedirect('/admin');

        $this->assertTrue(User::where('email', 'admin@loja.test')->firstOrFail()->isAdmin());
        $store = StoreSetting::query()->firstOrFail();
        $this->assertSame('#123456', $store->primary_color);
        $this->assertSame('Inter', $store->font_family);
        $this->assertNotNull($store->logo_path);
        Storage::disk('public')->assertExists($store->logo_path);
        $this->assertNotNull($store->installed_at);
        $this->assertSame('mercado_pago', IntegrationSetting::active('payment')->provider);
        $this->assertSame('flat_rate', IntegrationSetting::active('shipping')->provider);
        $this->get('/install?token=a-strong-installer-token')->assertNotFound();
    }

    public function test_branding_rejects_arbitrary_font_and_invalid_colors(): void
    {
        $this->withSession(['installer_authorized' => true])->post('/install/2', [
            'name' => 'Loja', 'short_name' => 'Loja', 'font_family' => 'url(https://evil.test/font)',
            'primary_color' => 'red', 'primary_dark_color' => '#000000', 'secondary_color' => '#111111',
            'accent_color' => '#222222', 'background_color' => '#ffffff',
        ])->assertSessionHasErrors(['font_family', 'primary_color']);
    }
}
