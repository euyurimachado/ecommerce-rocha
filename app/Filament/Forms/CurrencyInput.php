<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\TextInput;

class CurrencyInput
{
    public static function make(string $name): TextInput
    {
        return TextInput::make($name)
            ->prefix('R$')
            ->inputMode('decimal')
            ->placeholder('0,00')
            ->afterStateHydrated(function (TextInput $component, mixed $state): void {
                $component->state(self::format($state));
            })
            ->dehydrateStateUsing(fn (mixed $state): ?int => self::parse($state))
            ->rule('regex:/^\d{1,3}(\.\d{3})*(,\d{1,2})?$|^\d+(,\d{1,2})?$/')
            ->validationMessages([
                'regex' => 'Informe um valor válido, por exemplo 129,90.',
            ]);
    }

    public static function format(mixed $state): ?string
    {
        if ($state === null || $state === '') {
            return null;
        }

        if (is_string($state) && str_contains($state, ',')) {
            $state = self::parse($state);
        }

        return number_format((int) $state / 100, 2, ',', '.');
    }

    public static function parse(mixed $state): ?int
    {
        if ($state === null || trim((string) $state) === '') {
            return null;
        }

        if (is_int($state)) {
            return $state;
        }

        if (is_float($state)) {
            return (int) round($state * 100);
        }

        $normalized = preg_replace('/[^\d,.-]/', '', trim((string) $state)) ?? '';

        if (str_contains($normalized, ',')) {
            $normalized = str_replace('.', '', $normalized);
            $normalized = str_replace(',', '.', $normalized);
        } elseif (preg_match('/^-?\d+\.\d{1,2}$/', $normalized) !== 1) {
            $normalized = str_replace('.', '', $normalized);
        }

        if (! is_numeric($normalized)) {
            return null;
        }

        return (int) round((float) $normalized * 100);
    }
}
