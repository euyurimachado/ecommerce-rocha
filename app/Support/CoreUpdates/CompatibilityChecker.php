<?php

namespace App\Support\CoreUpdates;

use Illuminate\Foundation\Application;

class CompatibilityChecker
{
    public function __construct(
        private readonly ?string $phpVersion = null,
        private readonly ?string $laravelVersion = null,
    ) {}

    public function check(ReleaseManifest $manifest): CompatibilityResult
    {
        $reasons = [];
        $php = $this->phpVersion ?: PHP_VERSION;
        $laravel = $this->laravelVersion ?: Application::VERSION;

        if (version_compare($php, $manifest->minimumPhp, '<')) {
            $reasons[] = "Requer PHP {$manifest->minimumPhp} ou superior (atual: {$php}).";
        }

        if (version_compare($laravel, $manifest->minimumLaravel, '<')) {
            $reasons[] = "Requer Laravel {$manifest->minimumLaravel} ou superior (atual: {$laravel}).";
        }

        return new CompatibilityResult($reasons === [], $reasons);
    }
}
