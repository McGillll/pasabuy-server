<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PasabuyScheduleProductResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'uuid' => $this->uuid,
            'estimated_price' => $this->estimated_price,
            'max_quantity' => $this->max_quantity,
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
