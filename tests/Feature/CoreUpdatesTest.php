<?php

namespace Tests\Feature;

use App\Filament\Pages\SystemUpdates;
use App\Models\StoreSetting;
use App\Models\User;
use App\Support\CoreUpdates\CompatibilityChecker;
use App\Support\CoreUpdates\CoreVersion;
use App\Support\CoreUpdates\ReleaseChecker;
use App\Support\CoreUpdates\ReleaseManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class CoreUpdatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'core-updates.package' => 'vendor/ecommerce-core',
            'core-updates.channels.stable.manifest_url' => 'https://updates.example.test/stable.json',
            'core-updates.cache_ttl_seconds' => 86400,
        ]);
    }

    public function test_version_service_reads_the_core_version_from_composer_lock(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'core-lock-');
        file_put_contents($path, json_encode([
            'packages' => [['name' => 'vendor/ecommerce-core', 'version' => 'v1.4.2']],
            'packages-dev' => [],
        ], JSON_THROW_ON_ERROR));

        $service = new CoreVersion($path, 'vendor/ecommerce-core');

        $this->assertSame('1.4.2', $service->current());
        $this->assertSame('composer.lock', $service->source());

        unlink($path);
    }

    public function test_manifest_is_parsed_with_an_explicit_safe_schema(): void
    {
        $manifest = ReleaseManifest::fromArray($this->manifest([
            'shell_command' => 'rm -rf /',
            'sql' => 'DROP TABLE orders',
        ]));

        $this->assertSame('1.5.0', $manifest->latest);
        $this->assertSame('stable', $manifest->channel);
        $this->assertObjectNotHasProperty('shellCommand', $manifest);
    }

    public function test_invalid_manifest_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ReleaseManifest::fromArray($this->manifest(['checksum' => 'invalid']));
    }

    public function test_compatibility_check_reports_runtime_requirements(): void
    {
        $result = (new CompatibilityChecker('8.2.0', '12.0.0'))->check(
            ReleaseManifest::fromArray($this->manifest()),
        );

        $this->assertFalse($result->compatible);
        $this->assertCount(2, $result->reasons);
    }

    public function test_release_check_compares_versions_and_caches_the_manifest(): void
    {
        StoreSetting::query()->create([
            'name' => 'Loja', 'short_name' => 'Loja', 'installed_at' => now(), 'installation_version' => '1.4.2',
        ]);
        Http::fake(['updates.example.test/*' => Http::response($this->manifest(), 200)]);

        $checker = app(ReleaseChecker::class);
        $first = $checker->check();
        $second = $checker->check();

        $this->assertSame('available', $first->status);
        $this->assertSame('1.5.0', $first->manifest?->latest);
        $this->assertSame('available', $second->status);
        Http::assertSentCount(1);
    }

    public function test_release_server_failure_and_invalid_payload_are_safe(): void
    {
        StoreSetting::query()->create([
            'name' => 'Loja', 'short_name' => 'Loja', 'installed_at' => now(), 'installation_version' => '1.4.2',
        ]);
        Http::fake(['updates.example.test/*' => Http::response([], 500)]);

        $this->assertSame('unavailable', app(ReleaseChecker::class)->check()->status);

        Cache::flush();
        Http::fake(['updates.example.test/*' => Http::response(['latest' => 'anything'], 200)]);

        $this->assertSame('unavailable', app(ReleaseChecker::class)->check()->status);
    }

    public function test_only_admin_can_access_the_system_updates_page(): void
    {
        StoreSetting::query()->create([
            'name' => 'Loja', 'short_name' => 'Loja', 'installed_at' => now(), 'installation_version' => '1.0.0',
        ]);
        config(['core-updates.channels.stable.manifest_url' => null]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $manager = User::factory()->create(['role' => User::ROLE_MANAGER]);

        $this->actingAs($admin)->get(SystemUpdates::getUrl())->assertOk()->assertSee('Versão instalada');
        $this->actingAs($manager)->get(SystemUpdates::getUrl())->assertForbidden();
    }

    private function manifest(array $overrides = []): array
    {
        return array_merge([
            'latest' => '1.5.0',
            'channel' => 'stable',
            'minimum_php' => '8.3.0',
            'minimum_laravel' => '13.0.0',
            'released_at' => '2026-09-16T12:00:00-03:00',
            'security' => false,
            'breaking' => false,
            'release_notes' => "Novidades\nCorreções",
            'checksum' => str_repeat('a', 64),
        ], $overrides);
    }
}
