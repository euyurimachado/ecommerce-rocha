<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

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
                    ->helperText('Para valor fixo, informe em centavos. Para percentual, informe de 1 a 100.')
                    ->required()
                    ->integer()
                    ->minValue(1),
                TextInput::make('minimum_subtotal_cents')
                    ->label('Subtotal mínimo em centavos')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->default(0),
                TextInput::make('maximum_discount_cents')
                    ->label('Desconto máximo em centavos')
                    ->integer()
                    ->minValue(0),
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
