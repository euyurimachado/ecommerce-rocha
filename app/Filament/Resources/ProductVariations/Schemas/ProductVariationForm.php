<?php

namespace App\Filament\Resources\ProductVariations\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductVariationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->placeholder('Sabor, Cor, Tamanho...')
                    ->required()
                    ->maxLength(255),
                TextInput::make('sort_order')
                    ->label('Ordenação')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Ativa')
                    ->required()
                    ->default(true),
                Section::make('Opções')
                    ->schema([
                        Repeater::make('options')
                            ->relationship()
                            ->schema([
                                TextInput::make('value')
                                    ->label('Nome')
                                    ->placeholder('Chocolate, Morango, M...')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('sort_order')
                                    ->label('Ordenação')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                Toggle::make('is_active')
                                    ->label('Ativa')
                                    ->required()
                                    ->default(true),
                            ])
                            ->columns(3)
                            ->addActionLabel('Nova opção')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
