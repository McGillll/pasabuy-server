<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BasketResource extends JsonResource
{
    public function toArray(Request $request)
    {
        $items = $this->whenLoaded('items', fn () => $this->items);

        return [
            'uuid' => $this->uuid,
            'items' => BasketItemResource::collection($this->whenLoaded('items')),
            'total_items' => $items ? $items->count() : 0,
            'total_amount' => $items ? round($items->sum(fn ($item) => $item->product?->default_price * $item->quantity), 2) : 0,
        ];
    }
}
