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
            'product_uuid' => $this->whenLoaded('product', fn () => $this->product?->uuid),
            'product_name' => $this->product_name,
            'estimated_price' => $this->estimated_price,
            'actual_price' => $this->actual_price,
            'is_bonus' => $this->is_bonus,
            'confirmation_status' => $this->confirmation_status,
            'max_price' => $this->max_price,
            'auto_buy_if_under_max' => $this->auto_buy_if_under_max,
            'quantity' => $this->quantity,
            'subtotal' => $this->subtotal,
        ];
    }
}
