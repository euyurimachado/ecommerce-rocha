<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'short_description' => $this->short_description,
            'seo_description' => $this->seo_description,
            'is_featured' => $this->is_featured,
        ];
    }
}
