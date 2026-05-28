<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome')
                    ->required(),
                TextInput::make('slug')
                    ->label('Slug')
                    ->required(),
                TextInput::make('logo_path')
                    ->label('Logo'),
                Textarea::make('description')
                    ->label('Descrição')
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->label('Ativa')
                    ->required(),
                Toggle::make('is_featured')
                    ->label('Destaque')
                    ->required(),
            ]);
    }
}
