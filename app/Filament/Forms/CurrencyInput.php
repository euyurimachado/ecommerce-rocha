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
            ->formatStateUsing(fn (mixed $state): ?string => self::format($state))
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

        return number_format((int) $state / 100, 2, ',', '.');
    }

    public static function parse(mixed $state): ?int
    {
        if ($state === null || trim((string) $state) === '') {
            return null;
        }

        $normalized = str_replace(['.', ','], ['', '.'], trim((string) $state));

        return (int) round((float) $normalized * 100);
    }
}
