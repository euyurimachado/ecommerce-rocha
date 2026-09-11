<?php

namespace App\Support\Products;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductSeo
{
    public function uniqueSlug(string $name, ?int $ignoreProductId = null): string
    {
        $base = Str::slug($name) ?: 'produto';
        $candidate = $base;
        $suffix = 2;

        while (Product::query()
            ->when($ignoreProductId, fn ($query) => $query->whereKeyNot($ignoreProductId))
            ->where('slug', $candidate)
            ->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }

    public function metaDescription(?string $shortDescription, ?string $name): string
    {
        $source = filled($shortDescription)
            ? (string) $shortDescription
            : trim((string) $name).' na Rocha Sports.';

        $plainText = Str::of(html_entity_decode(strip_tags($source), ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            ->squish()
            ->toString();

        if (mb_strlen($plainText) <= 158) {
            return $plainText;
        }

        $trimmed = mb_substr($plainText, 0, 158);
        $lastSpace = mb_strrpos($trimmed, ' ');

        return rtrim($lastSpace === false ? $trimmed : mb_substr($trimmed, 0, $lastSpace), ' ,.;:-').'…';
    }
}
