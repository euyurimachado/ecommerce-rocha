<?php

namespace App\Filament\Resources\ProductVariations\Pages;

use App\Filament\Resources\ProductVariations\ProductVariationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProductVariations extends ListRecords
{
    protected static string $resource = ProductVariationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
