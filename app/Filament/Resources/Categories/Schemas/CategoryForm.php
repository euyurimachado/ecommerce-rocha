<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('parent_id')
                    ->label('Categoria pai')
                    ->relationship('parent', 'name'),
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required(),
                TextInput::make('icon')
                    ->label('Ícone'),
                TextInput::make('short_description')
                    ->label('Descrição curta'),
                Textarea::make('seo_description')
                    ->label('Descrição para SEO')
                    ->columnSpanFull(),
                TextInput::make('meta_title')
                    ->label('Meta title'),
                TextInput::make('meta_description')
                    ->label('Meta description'),
                TextInput::make('sort_order')
                    ->label('Ordenação')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Ativa')
                    ->required(),
                Toggle::make('is_featured')
                    ->label('Destaque')
                    ->required(),
            ]);
    }
}
