<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PasabuyScheduleProductBuyerResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'customer_name' => $this->order->customer_name,
            'quantity' => $this->quantity,
            'estimated_price' => $this->estimated_price,
            'actual_price' => $this->actual_price,
            'subtotal' => $this->subtotal,
            'status' => $this->order->status,
        ];
    }
}
