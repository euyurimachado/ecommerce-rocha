<?php

namespace App\Support\CoreUpdates;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

final readonly class ReleaseManifest
{
    public function __construct(
        public string $latest,
        public string $channel,
        public string $minimumPhp,
        public string $minimumLaravel,
        public CarbonImmutable $releasedAt,
        public bool $security,
        public bool $breaking,
        public string $releaseNotes,
        public string $checksum,
    ) {}

    public static function fromArray(array $data): self
    {
        foreach (['latest', 'channel', 'minimum_php', 'minimum_laravel', 'released_at', 'release_notes', 'checksum'] as $required) {
            if (! is_string($data[$required] ?? null) || trim($data[$required]) === '') {
                throw new InvalidArgumentException("Manifesto de release inválido: {$required} ausente.");
            }
        }

        $latest = ltrim(trim($data['latest']), 'vV');

        if (! preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$/', $latest)) {
            throw new InvalidArgumentException('Manifesto de release inválido: versão fora do padrão SemVer.');
        }

        if (! in_array($data['channel'], ['stable', 'beta'], true)) {
            throw new InvalidArgumentException('Manifesto de release inválido: canal desconhecido.');
        }

        foreach (['minimum_php', 'minimum_laravel'] as $requirement) {
            if (! preg_match('/^\d+\.\d+(?:\.\d+)?$/', trim($data[$requirement]))) {
                throw new InvalidArgumentException("Manifesto de release inválido: {$requirement} malformado.");
            }
        }

        $checksum = strtolower(trim($data['checksum']));

        if (! preg_match('/^[a-f0-9]{64}$/', $checksum)) {
            throw new InvalidArgumentException('Manifesto de release inválido: checksum SHA-256 ausente ou malformado.');
        }

        try {
            $releasedAt = CarbonImmutable::parse($data['released_at']);
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('Manifesto de release inválido: data de publicação inválida.', previous: $exception);
        }

        return new self(
            latest: $latest,
            channel: $data['channel'],
            minimumPhp: trim($data['minimum_php']),
            minimumLaravel: trim($data['minimum_laravel']),
            releasedAt: $releasedAt,
            security: filter_var($data['security'] ?? false, FILTER_VALIDATE_BOOL),
            breaking: filter_var($data['breaking'] ?? false, FILTER_VALIDATE_BOOL),
            releaseNotes: trim($data['release_notes']),
            checksum: $checksum,
        );
    }
}
