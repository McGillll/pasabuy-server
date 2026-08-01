<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PasabuyScheduleChecklistItemResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'product_uuid' => $this->product?->uuid,
            'product_name' => $this->product_name,
            'category_name' => $this->product?->category?->name,
            'quantity' => $this->quantity,
            'is_checked' => $this->is_checked,
            'checked_at' => $this->checked_at,
            'additional_quantity' => $this->additional_quantity,
            'return_quantity' => $this->return_quantity,
            'default_price' => $this->product?->default_price,
            'actual_price' => $this->schedule_product?->actual_price,
            'product_status' => $this->schedule_product?->product_status,
        ];
    }
}
