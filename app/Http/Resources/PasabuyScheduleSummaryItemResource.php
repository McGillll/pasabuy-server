<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PasabuyScheduleSummaryItemResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'product_uuid' => $this->product?->uuid,
            'product_name' => $this->product?->name ?? 'Deleted product',
            'category_name' => $this->product?->category?->name,
            'default_price' => $this->product?->default_price ?? 0,
            'total_quantity' => (int) $this->total_quantity,
            'subtotal' => ($this->product?->default_price ?? 0) * $this->total_quantity,
        ];
    }
}
