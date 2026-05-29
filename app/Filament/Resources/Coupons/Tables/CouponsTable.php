<?php

namespace App\Filament\Resources\Coupons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('name')
                    ->label('Campanha')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => $state === 'percent' ? 'Percentual' : 'Valor fixo'),
                TextColumn::make('value')
                    ->label('Valor')
                    ->formatStateUsing(fn (int $state, $record): string => $record->type === 'percent' ? $state.'%' : 'R$ '.number_format($state / 100, 2, ',', '.')),
                TextColumn::make('formatted_minimum_subtotal')
                    ->label('Subtotal mínimo'),
                TextColumn::make('used_count')
                    ->label('Usos')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('usage_limit')
                    ->label('Limite')
                    ->placeholder('Ilimitado'),
                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('expires_at')
                    ->label('Expira em')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
