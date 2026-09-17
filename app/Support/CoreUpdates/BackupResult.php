<?php

namespace App\Support\CoreUpdates;

final readonly class BackupResult
{
    public function __construct(
        public bool $successful,
        public ?string $reference = null,
        public ?string $message = null,
    ) {}
}
