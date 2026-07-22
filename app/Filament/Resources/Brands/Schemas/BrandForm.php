<?php

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\FileUpload;
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
                FileUpload::make('logo_path')
                    ->label('Logo personalizada')
                    ->helperText('Substitui a logo padrão do site. Formatos aceitos: JPG, PNG e WebP.')
                    ->image()
                    ->imageEditor()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(4096)
                    ->disk('public')
                    ->directory('brands/custom')
                    ->visibility('public')
                    ->fetchFileInformation(false)
                    ->columnSpanFull(),
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
