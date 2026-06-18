<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'image_url' => $this->image_path ? asset('storage/'.$this->image_path) : null,
            'gallery_image_urls' => $this->galleryImageUrls(),
            'brand' => BrandResource::make($this->whenLoaded('brand')),
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'weight' => $this->weight,
            'flavor' => $this->flavor,
            'variations' => $this->variationOptions(),
            'short_description' => $this->short_description,
            'description' => $this->description,
            'benefits' => $this->benefits ?? [],
            'usage_instructions' => $this->usage_instructions,
            'ingredients' => $this->ingredients,
            'price' => [
                'cents' => $this->price_cents,
                'formatted' => $this->formatted_price,
                'compare_at_cents' => $this->compare_at_price_cents,
                'compare_at_formatted' => $this->formatted_compare_at_price,
            ],
            'rating' => (float) $this->rating,
            'reviews_count' => $this->reviews_count,
            'sales_count' => $this->sales_count,
            'available_quantity' => $this->available_quantity,
            'badges' => [
                'featured' => $this->is_featured,
                'offer' => $this->is_offer,
                'pickup' => $this->allows_pickup,
                'local_delivery' => $this->allows_local_delivery,
            ],
            'seo' => [
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
            ],
        ];
    }
}
