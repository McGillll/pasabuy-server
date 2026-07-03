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
            'product_name' => $this->product_name,
            'product_price' => $this->product_price,
            'quantity' => $this->quantity,
            'subtotal' => $this->subtotal,
        ];
    }
}
