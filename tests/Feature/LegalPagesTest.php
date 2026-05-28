<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_renders_cookie_preferences_entry_points(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Preferências de cookies')
            ->assertSee('data-cookie-consent', false)
            ->assertSee('data-cookie-preferences-open', false);
    }

    public function test_privacy_policy_is_available_in_portuguese(): void
    {
        $this->get('/politica-de-privacidade')
            ->assertOk()
            ->assertSee('Política de Privacidade')
            ->assertSee('Direitos do titular')
            ->assertSee('Segurança');
    }

    public function test_cookie_policy_has_preference_center(): void
    {
        $this->get('/politica-de-cookies')
            ->assertOk()
            ->assertSee('Política de Cookies')
            ->assertSee('Gerenciar preferências')
            ->assertSee('Analíticos')
            ->assertSee('Marketing');
    }
}
