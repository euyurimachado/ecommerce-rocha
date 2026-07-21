<?php

namespace Tests\Unit;

use App\Filament\Forms\CurrencyInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CurrencyInputTest extends TestCase
{
    #[DataProvider('currencyValues')]
    public function test_it_converts_brazilian_currency_to_cents(string $input, int $cents): void
    {
        $this->assertSame($cents, CurrencyInput::parse($input));
        $this->assertSame($input, CurrencyInput::format($cents));
    }

    public static function currencyValues(): array
    {
        return [
            ['0,00', 0],
            ['89,90', 8990],
            ['129,90', 12990],
            ['1.299,99', 129999],
        ];
    }
}
