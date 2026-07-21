<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Filament\Forms\CurrencyInput;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Código')
                    ->required()
                    ->maxLength(40)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state): ?string => $state ? mb_strtoupper(trim($state)) : null),
                TextInput::make('name')
                    ->label('Nome da campanha')
                    ->required()
                    ->maxLength(120),
                Select::make('type')
                    ->label('Tipo')
                    ->required()
                    ->options([
                        'fixed' => 'Valor fixo',
                        'percent' => 'Percentual',
                    ])
                    ->default('fixed'),
                TextInput::make('value')
                    ->label('Valor')
                    ->helperText('Para valor fixo, informe em reais (por exemplo 20,00). Para percentual, informe de 1 a 100.')
                    ->required()
                    ->inputMode('decimal')
                    ->formatStateUsing(fn (mixed $state, Get $get): mixed => $get('type') === 'fixed'
                        ? CurrencyInput::format($state)
                        : $state)
                    ->dehydrateStateUsing(fn (mixed $state, Get $get): mixed => $get('type') === 'fixed'
                        ? CurrencyInput::parse($state)
                        : (int) $state)
                    ->rules(fn (Get $get): array => $get('type') === 'fixed'
                        ? ['regex:/^\d{1,3}(\.\d{3})*(,\d{1,2})?$|^\d+(,\d{1,2})?$/']
                        : ['integer', 'min:1', 'max:100']),
                CurrencyInput::make('minimum_subtotal_cents')
                    ->label('Subtotal mínimo')
                    ->required()
                    ->default(0),
                CurrencyInput::make('maximum_discount_cents')
                    ->label('Desconto máximo'),
                TextInput::make('usage_limit')
                    ->label('Limite de uso')
                    ->integer()
                    ->minValue(1),
                TextInput::make('used_count')
                    ->label('Usos realizados')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Ativo')
                    ->required()
                    ->default(true),
                DateTimePicker::make('starts_at')
                    ->label('Inicia em'),
                DateTimePicker::make('expires_at')
                    ->label('Expira em'),
            ]);
    }
}
