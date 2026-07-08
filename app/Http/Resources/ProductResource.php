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
            'last_known_price' => $this->whenLoaded('latestScheduleProduct', fn () => $this->latestScheduleProduct?->actual_price ?? $this->latestScheduleProduct?->estimated_price ?? null),
            'last_known_price_source' => $this->whenLoaded('latestScheduleProduct', fn () => $this->latestScheduleProduct === null
                ? null
                : ($this->latestScheduleProduct->actual_price !== null ? 'actual' : 'estimated')),
        ];
    }
}
