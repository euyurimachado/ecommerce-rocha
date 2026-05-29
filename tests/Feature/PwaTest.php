<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_exposes_pwa_metadata(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee(route('pwa.manifest'), false)
            ->assertSee('name="theme-color"', false)
            ->assertSee('apple-mobile-web-app-capable', false);
    }

    public function test_manifest_is_installable_and_localized(): void
    {
        $this->get('/site.webmanifest')
            ->assertOk()
            ->assertHeader('content-type', 'application/manifest+json')
            ->assertJsonPath('name', 'Rocha Sports')
            ->assertJsonPath('short_name', 'Rocha')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('lang', 'pt-BR')
            ->assertJsonPath('theme_color', '#0098d7')
            ->assertJsonPath('shortcuts.0.name', 'Buscar suplementos');
    }

    public function test_offline_page_is_available_in_portuguese(): void
    {
        $this->get('/offline')
            ->assertOk()
            ->assertSee('Você está offline')
            ->assertSee('Tentar novamente');
    }

    public function test_service_worker_and_pwa_icon_are_public_assets(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertFileExists(public_path('images/pwa-icon.svg'));
        $this->assertStringContainsString('/sw.js', file_get_contents(resource_path('js/app.js')));
        $this->assertStringContainsString('/offline', file_get_contents(public_path('sw.js')));
        $this->assertStringContainsString('rocha-sports-v1', file_get_contents(public_path('sw.js')));
    }
}
