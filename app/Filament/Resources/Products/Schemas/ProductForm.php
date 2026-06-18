<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'name')
                    ->required(),
                Select::make('brand_id')
                    ->label('Marca')
                    ->relationship('brand', 'name'),
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required(),
                TextInput::make('sku')
                    ->label('SKU')
                    ->required(),
                Section::make('Imagens')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Imagem destacada')
                            ->image()
                            ->disk('public')
                            ->directory('products')
                            ->visibility('public'),
                        FileUpload::make('gallery_images')
                            ->label('Galeria de imagens')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->disk('public')
                            ->directory('products/gallery')
                            ->visibility('public'),
                    ])
                    ->columnSpanFull(),
                TextInput::make('weight')
                    ->label('Peso/volume'),
                TextInput::make('flavor')
                    ->label('Sabor'),
                Repeater::make('variations')
                    ->label('Variações do produto')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome da variação')
                            ->placeholder('Sabor, Cor, Tamanho...')
                            ->required(),
                        TagsInput::make('values')
                            ->label('Opções')
                            ->placeholder('Digite uma opção e pressione Enter')
                            ->required(),
                    ])
                    ->addActionLabel('Nova variação')
                    ->reorderable()
                    ->columnSpanFull(),
                Textarea::make('short_description')
                    ->label('Descrição curta')
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('Descrição completa')
                    ->columnSpanFull(),
                Textarea::make('benefits')
                    ->label('Benefícios')
                    ->columnSpanFull(),
                Textarea::make('usage_instructions')
                    ->label('Modo de uso')
                    ->columnSpanFull(),
                Textarea::make('ingredients')
                    ->label('Ingredientes')
                    ->columnSpanFull(),
                TextInput::make('stock_quantity')
                    ->label('Estoque')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('reserved_quantity')
                    ->label('Estoque reservado')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('price_cents')
                    ->label('Preço')
                    ->required()
                    ->numeric(),
                TextInput::make('compare_at_price_cents')
                    ->label('Preço anterior')
                    ->numeric(),
                TextInput::make('rating')
                    ->label('Avaliação')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('reviews_count')
                    ->label('Avaliações')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('sales_count')
                    ->label('Vendas')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Ativo')
                    ->required(),
                Toggle::make('is_featured')
                    ->label('Destaque')
                    ->required(),
                Toggle::make('is_offer')
                    ->label('Oferta')
                    ->required(),
                Toggle::make('allows_pickup')
                    ->label('Permite retirada')
                    ->required(),
                Toggle::make('allows_local_delivery')
                    ->label('Permite entrega local')
                    ->required(),
                TextInput::make('meta_title')
                    ->label('Meta title'),
                TextInput::make('meta_description')
                    ->label('Meta description'),
            ]);
    }
}
