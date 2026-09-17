<?php

namespace App\Support\CoreUpdates;

use App\Models\StoreSetting;
use Composer\InstalledVersions;
use Illuminate\Support\Facades\Schema;

class CoreVersion
{
    private string $source = 'development';

    public function __construct(
        private readonly ?string $lockPath = null,
        private readonly ?string $packageName = null,
    ) {}

    public function current(): string
    {
        $package = $this->packageName ?: (string) config('core-updates.package');
        $locked = $this->versionFromLock($package);

        if ($locked !== null) {
            $this->source = 'composer.lock';

            return $this->normalize($locked);
        }

        if (class_exists(InstalledVersions::class) && InstalledVersions::isInstalled($package)) {
            $this->source = 'composer';

            return $this->normalize((string) (InstalledVersions::getPrettyVersion($package) ?: InstalledVersions::getVersion($package)));
        }

        try {
            if (Schema::hasTable('store_settings')) {
                $legacyVersion = (string) StoreSetting::current()->installation_version;

                if ($legacyVersion !== '') {
                    $this->source = 'legacy-installation';

                    return $this->normalize($legacyVersion);
                }
            }
        } catch (\Throwable) {
            // The application may be booting before the database is available.
        }

        $this->source = 'development';

        return 'development';
    }

    public function source(): string
    {
        return $this->source;
    }

    private function versionFromLock(string $package): ?string
    {
        $path = $this->lockPath ?: base_path('composer.lock');

        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        try {
            $lock = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }

        foreach ([...($lock['packages'] ?? []), ...($lock['packages-dev'] ?? [])] as $dependency) {
            if (($dependency['name'] ?? null) === $package && is_string($dependency['version'] ?? null)) {
                return $dependency['version'];
            }
        }

        return null;
    }

    private function normalize(string $version): string
    {
        return ltrim(trim($version), 'vV');
    }
}
