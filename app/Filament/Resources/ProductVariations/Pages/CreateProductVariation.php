<?php

namespace App\Filament\Resources\ProductVariations\Pages;

use App\Filament\Resources\ProductVariations\ProductVariationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProductVariation extends CreateRecord
{
    protected static string $resource = ProductVariationResource::class;
}
