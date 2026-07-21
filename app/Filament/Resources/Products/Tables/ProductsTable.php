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
                    ->label('Categoria')
                    ->searchable(),
                TextColumn::make('brand.name')
                    ->label('Marca')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                ImageColumn::make('image_path'),
                TextColumn::make('weight')
                    ->label('Peso/volume')
                    ->searchable(),
                TextColumn::make('price_cents')
                    ->label('Preço')
                    ->money('BRL', divideBy: 100)
                    ->sortable(),
                TextColumn::make('compare_at_price_cents')
                    ->label('Preço anterior')
                    ->money('BRL', divideBy: 100)
                    ->sortable(),
                TextColumn::make('rating')
                    ->label('Avaliação')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('reviews_count')
                    ->label('Avaliações')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sales_count')
                    ->label('Vendas')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                IconColumn::make('is_featured')
                    ->label('Destaque')
                    ->boolean(),
                IconColumn::make('is_offer')
                    ->label('Oferta')
                    ->boolean(),
                IconColumn::make('show_in_weight_loss')
                    ->label('Home: Emagrecer')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('show_in_energy')
                    ->label('Home: Energia')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('show_in_mass_gain')
                    ->label('Home: Massa')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('show_in_whey_festival')
                    ->label('Home: Whey')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('show_in_creatine_house')
                    ->label('Home: Creatina')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('allows_pickup')
                    ->label('Retirada')
                    ->boolean(),
                IconColumn::make('allows_local_delivery')
                    ->label('Entrega local')
                    ->boolean(),
                TextColumn::make('meta_title')
                    ->searchable(),
                TextColumn::make('meta_description')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Atualizado em')
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
