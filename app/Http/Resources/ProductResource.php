<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
            'is_active' => $this->is_active,
            'default_price' => $this->default_price,
            'default_product_status' => $this->default_product_status,
            'category' => new ProductCategoryResource($this->whenLoaded('category')),
            'last_confirmed_price' => $this->whenLoaded('latestScheduleProduct', fn () => $this->latestScheduleProduct?->actual_price),
            'last_product_status' => $this->whenLoaded('latestScheduleProduct', fn () => $this->latestScheduleProduct?->product_status),
        ];
    }
}
