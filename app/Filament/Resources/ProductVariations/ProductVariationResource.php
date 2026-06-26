<?php

namespace App\Filament\Resources\ProductVariations;

use App\Filament\Resources\ProductVariations\Pages\CreateProductVariation;
use App\Filament\Resources\ProductVariations\Pages\EditProductVariation;
use App\Filament\Resources\ProductVariations\Pages\ListProductVariations;
use App\Filament\Resources\ProductVariations\Schemas\ProductVariationForm;
use App\Filament\Resources\ProductVariations\Tables\ProductVariationsTable;
use App\Models\ProductVariation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductVariationResource extends Resource
{
    protected static ?string $model = ProductVariation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?string $navigationLabel = 'Variações';

    protected static ?string $modelLabel = 'Variação';

    protected static ?string $pluralModelLabel = 'Variações';

    protected static string|UnitEnum|null $navigationGroup = 'Catálogo';

    public static function form(Schema $schema): Schema
    {
        return ProductVariationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductVariationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductVariations::route('/'),
            'create' => CreateProductVariation::route('/create'),
            'edit' => EditProductVariation::route('/{record}/edit'),
        ];
    }
}
