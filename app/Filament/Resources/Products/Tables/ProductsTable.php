<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name')
                    ->searchable(),
                TextColumn::make('brand.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                ImageColumn::make('image_path'),
                TextColumn::make('weight')
                    ->searchable(),
                TextColumn::make('flavor')
                    ->searchable(),
                TextColumn::make('stock_quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reserved_quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('price_cents')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('compare_at_price_cents')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('rating')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reviews_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sales_count')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                IconColumn::make('is_featured')
                    ->boolean(),
                IconColumn::make('is_offer')
                    ->boolean(),
                IconColumn::make('allows_pickup')
                    ->boolean(),
                IconColumn::make('allows_local_delivery')
                    ->boolean(),
                TextColumn::make('meta_title')
                    ->searchable(),
                TextColumn::make('meta_description')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
