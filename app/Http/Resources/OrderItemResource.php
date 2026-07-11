<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'uuid' => $this->uuid,
            'product_uuid' => $this->whenLoaded('pasabuyScheduleProduct', fn () => $this->pasabuyScheduleProduct?->product?->uuid),
            'product_name' => $this->product_name,
            'estimated_price' => $this->estimated_price,
            'actual_price' => $this->actual_price,
            'is_out_of_stock' => $this->is_out_of_stock,
            'quantity' => $this->quantity,
            'bonus_quantity' => $this->bonus_quantity,
            'subtotal' => $this->subtotal,
        ];
    }
}
