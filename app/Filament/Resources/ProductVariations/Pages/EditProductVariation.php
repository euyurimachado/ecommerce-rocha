<?php

namespace App\Filament\Resources\ProductVariations\Pages;

use App\Filament\Resources\ProductVariations\ProductVariationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProductVariation extends EditRecord
{
    protected static string $resource = ProductVariationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
