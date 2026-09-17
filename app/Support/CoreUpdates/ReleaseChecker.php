<?php

namespace App\Support\CoreUpdates;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ReleaseChecker
{
    public function __construct(
        private readonly CoreVersion $coreVersion,
        private readonly CompatibilityChecker $compatibilityChecker,
    ) {}

    public function check(?string $channel = null, bool $force = false): ReleaseCheckResult
    {
        $channel ??= (string) config('core-updates.default_channel', 'stable');
        $installed = $this->coreVersion->current();
        $source = $this->coreVersion->source();

        if (! in_array($channel, ['stable', 'beta'], true)) {
            return new ReleaseCheckResult($installed, $source, $channel, 'unavailable', message: 'Canal de atualização inválido.');
        }

        $url = (string) config("core-updates.channels.{$channel}.manifest_url");

        if ($url === '') {
            return new ReleaseCheckResult($installed, $source, $channel, 'not_configured', message: 'A fonte de releases ainda não foi configurada.');
        }

        $cacheKey = "ecommerce-core.release-manifest.{$channel}";

        if ($force) {
            Cache::forget($cacheKey);
        }

        try {
            $manifest = Cache::remember(
                $cacheKey,
                max(60, (int) config('core-updates.cache_ttl_seconds', 86400)),
                fn (): ReleaseManifest => $this->fetch($url, $channel),
            );
        } catch (\Throwable) {
            return new ReleaseCheckResult($installed, $source, $channel, 'unavailable', message: 'Não foi possível consultar releases com segurança agora.');
        }

        $compatibility = $this->compatibilityChecker->check($manifest);
        $hasComparableVersion = preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', $installed) === 1;
        $available = $hasComparableVersion && version_compare($manifest->latest, $installed, '>');

        return new ReleaseCheckResult(
            installedVersion: $installed,
            installedVersionSource: $source,
            channel: $channel,
            status: $available ? ($compatibility->compatible ? 'available' : 'incompatible') : 'current',
            manifest: $manifest,
            compatibility: $compatibility,
            message: $hasComparableVersion ? null : 'A versão atual ainda não vem de um package Composer do core.',
        );
    }

    private function fetch(string $url, string $channel): ReleaseManifest
    {
        $response = Http::acceptJson()
            ->timeout(max(1, (int) config('core-updates.request_timeout_seconds', 8)))
            ->get($url)
            ->throw();

        $manifest = ReleaseManifest::fromArray($response->json());

        if ($manifest->channel !== $channel) {
            throw new \UnexpectedValueException('O canal retornado não corresponde ao canal solicitado.');
        }

        return $manifest;
    }
}
