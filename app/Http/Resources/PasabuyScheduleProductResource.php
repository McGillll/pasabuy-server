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
            'actual_price' => $this->actual_price,
            'promo_status' => $this->promo_status,
            'price_confirmed_at' => $this->price_confirmed_at,
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
