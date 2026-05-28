<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                Select::make('brand_id')
                    ->relationship('brand', 'name'),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('sku')
                    ->label('SKU')
                    ->required(),
                FileUpload::make('image_path')
                    ->image(),
                TextInput::make('weight'),
                TextInput::make('flavor'),
                Textarea::make('short_description')
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->columnSpanFull(),
                Textarea::make('benefits')
                    ->columnSpanFull(),
                Textarea::make('usage_instructions')
                    ->columnSpanFull(),
                Textarea::make('ingredients')
                    ->columnSpanFull(),
                TextInput::make('stock_quantity')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('reserved_quantity')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('price_cents')
                    ->required()
                    ->numeric(),
                TextInput::make('compare_at_price_cents')
                    ->numeric(),
                TextInput::make('rating')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('reviews_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('sales_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required(),
                Toggle::make('is_featured')
                    ->required(),
                Toggle::make('is_offer')
                    ->required(),
                Toggle::make('allows_pickup')
                    ->required(),
                Toggle::make('allows_local_delivery')
                    ->required(),
                TextInput::make('meta_title'),
                TextInput::make('meta_description'),
            ]);
    }
}
