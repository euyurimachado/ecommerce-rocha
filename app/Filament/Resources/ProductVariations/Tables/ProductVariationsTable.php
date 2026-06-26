<?php

namespace App\Filament\Resources\ProductVariations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductVariationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('options_count')
                    ->label('Opções')
                    ->counts('options')
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Ordenação')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Ativa')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Atualizada em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
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
