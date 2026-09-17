<?php

namespace App\Support\CoreUpdates;

final readonly class ReleaseCheckResult
{
    public function __construct(
        public string $installedVersion,
        public string $installedVersionSource,
        public string $channel,
        public string $status,
        public ?ReleaseManifest $manifest = null,
        public ?CompatibilityResult $compatibility = null,
        public ?string $message = null,
    ) {}

    public function updateAvailable(): bool
    {
        return $this->status === 'available';
    }
}
