<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'cta_label' => $this->cta_label,
            'url' => $this->url,
            'placement' => $this->placement,
            'device' => $this->device,
            'image_url' => $this->image_path ? asset('storage/'.$this->image_path) : null,
        ];
    }
}
