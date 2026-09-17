<?php

namespace App\Support\CoreUpdates;

final readonly class CompatibilityResult
{
    /** @param list<string> $reasons */
    public function __construct(public bool $compatible, public array $reasons = []) {}
}
