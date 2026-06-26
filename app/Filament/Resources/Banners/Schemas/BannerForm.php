<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class BannerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->label('Título')
                    ->required(),
                TextInput::make('subtitle')
                    ->label('Subtítulo'),
                TextInput::make('cta_label')
                    ->label('Texto do botão'),
                TextInput::make('url')
                    ->label('URL')
                    ->url(),
                FileUpload::make('image_path')
                    ->label('Imagem')
                    ->image()
                    ->disk('public')
                    ->directory('banners')
                    ->visibility('public'),
                Select::make('placement')
                    ->label('Posição')
                    ->options([
                        'home_hero' => 'Home - slider principal',
                        'home_energy' => 'Home - seção Para ter energia',
                    ])
                    ->searchable()
                    ->required()
                    ->default('home_hero'),
                TextInput::make('device')
                    ->label('Dispositivo')
                    ->required()
                    ->default('all'),
                TextInput::make('sort_order')
                    ->label('Ordenação')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Ativo')
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->label('Inicia em'),
                DateTimePicker::make('ends_at')
                    ->label('Termina em'),
            ]);
    }
}
