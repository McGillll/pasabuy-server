<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PasabuyScheduleSummaryItemResource extends JsonResource
{
    public function toArray(Request $request)
    {
        $scheduleProduct = $this->schedule_product;
        $defaultPrice = $this->product?->default_price ?? 0;
        $actualPrice = $scheduleProduct?->actual_price;
        $productStatus = $scheduleProduct?->product_status ?? 'none';
        $unitPrice = $actualPrice !== null ? $actualPrice : $defaultPrice;
        $totalQuantity = (int) $this->total_quantity;

        return [
            'product_uuid' => $this->product?->uuid,
            'product_name' => $this->product?->name ?? 'Deleted product',
            'category_name' => $this->product?->category?->name,
            'default_price' => $defaultPrice,
            'unit_price' => $unitPrice,
            'actual_price' => $actualPrice,
            'price_confirmed_at' => $scheduleProduct?->price_confirmed_at,
            'product_status' => $productStatus,
            'total_quantity' => $totalQuantity,
            'subtotal' => $productStatus === 'out_of_stock' ? 0 : $unitPrice * $totalQuantity,
        ];
    }
}
