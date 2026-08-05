<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BasketItemResource extends JsonResource
{
    public function toArray(Request $request)
    {
        return [
            'uuid' => $this->uuid,
            'product_uuid' => $this->product?->uuid,
            'product_name' => $this->product?->name,
            'product_image_url' => $this->product?->image_path ? Storage::url($this->product->image_path) : null,
            'price' => $this->product?->default_price,
            'quantity' => $this->quantity,
            'subtotal' => $this->product ? round($this->product->default_price * $this->quantity, 2) : null,
        ];
    }
}
