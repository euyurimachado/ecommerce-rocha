<?php

namespace App\Filament\Resources\Banners\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BannersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),
                TextColumn::make('subtitle')
                    ->label('Subtítulo')
                    ->searchable(),
                TextColumn::make('cta_label')
                    ->label('Texto do botão')
                    ->searchable(),
                TextColumn::make('url')
                    ->searchable(),
                ImageColumn::make('image_path'),
                TextColumn::make('placement')
                    ->label('Posição')
                    ->searchable(),
                TextColumn::make('device')
                    ->label('Dispositivo')
                    ->searchable(),
                TextColumn::make('sort_order')
                    ->label('Ordenação')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
                TextColumn::make('starts_at')
                    ->label('Inicia em')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Termina em')
                    ->dateTime()
                    ->sortable(),
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
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
