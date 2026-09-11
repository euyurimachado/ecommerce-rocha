<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use App\Support\Products\DuplicateProduct;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(self::toggleableColumns([
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
                TextColumn::make('stock_quantity')
                    ->label('Estoque')
                    ->numeric()
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
            ]))
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Todos')
                    ->trueLabel('Ativos')
                    ->falseLabel('Inativos'),
                SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('brand_id')
                    ->label('Marca')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('is_offer')
                    ->label('Oferta')
                    ->placeholder('Todos')
                    ->trueLabel('Em oferta')
                    ->falseLabel('Sem oferta'),
                TernaryFilter::make('is_featured')
                    ->label('Destaque')
                    ->placeholder('Todos')
                    ->trueLabel('Em destaque')
                    ->falseLabel('Sem destaque'),
            ])
            ->filtersTriggerAction(fn (Action $action): Action => $action
                ->label('Filtrar produtos')
                ->button())
            ->toggleColumnsTriggerAction(fn (Action $action): Action => $action
                ->label('Configurar colunas')
                ->button())
            ->recordActions([
                EditAction::make(),
                Action::make('duplicate')
                    ->label('Duplicar produto')
                    ->icon('heroicon-o-square-2-stack')
                    ->requiresConfirmation()
                    ->action(function (Product $record) {
                        $copy = app(DuplicateProduct::class)($record);

                        Notification::make()->success()->title('Produto duplicado com sucesso')->send();

                        return redirect(ProductResource::getUrl('edit', ['record' => $copy, 'auto_seo' => 1]));
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @param  array<Column>  $columns
     * @return array<Column>
     */
    private static function toggleableColumns(array $columns): array
    {
        $hiddenByDefault = [
            'show_in_weight_loss',
            'show_in_energy',
            'show_in_mass_gain',
            'show_in_whey_festival',
            'show_in_creatine_house',
            'created_at',
            'updated_at',
        ];

        return array_map(function (Column $column) use ($hiddenByDefault): Column {
            if (! in_array($column->getName(), $hiddenByDefault, true)) {
                $column->toggleable();
            }

            return $column;
        }, $columns);
    }
}
