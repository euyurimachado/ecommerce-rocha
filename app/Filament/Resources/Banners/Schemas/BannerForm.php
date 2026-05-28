<?php

namespace App\Filament\Resources\Banners\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
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
                    ->required(),
                TextInput::make('subtitle'),
                TextInput::make('cta_label'),
                TextInput::make('url')
                    ->url(),
                FileUpload::make('image_path')
                    ->image(),
                TextInput::make('placement')
                    ->required()
                    ->default('home_hero'),
                TextInput::make('device')
                    ->required()
                    ->default('all'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required(),
                DateTimePicker::make('starts_at'),
                DateTimePicker::make('ends_at'),
            ]);
    }
}
