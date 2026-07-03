<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'uuid' => $this->uuid,
            'customer_name' => $this->customer_name,
            'customer_contact' => $this->customer_contact,
            'status' => $this->status,
            'total_amount' => $this->total_amount,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
        ];
    }
}
