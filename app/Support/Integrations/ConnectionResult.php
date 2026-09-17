<?php

namespace App\Support\Integrations;

final readonly class ConnectionResult
{
    public function __construct(public bool $successful, public string $message) {}

    public static function success(string $message = 'Conexão validada.'): self
    {
        return new self(true, $message);
    }

    public static function failure(string $message): self
    {
        return new self(false, $message);
    }
}
