<?php

namespace Tests\Feature;

use App\Filament\Resources\IntegrationSettings\IntegrationSettingResource;
use App\Models\IntegrationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            ->assertDontSee('another-secret');
    }
}
