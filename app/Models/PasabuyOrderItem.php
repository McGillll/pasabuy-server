<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['product_name', 'estimated_price', 'actual_price', 'quantity', 'bonus_quantity', 'subtotal'])]
class PasabuyOrderItem extends Model
{
    protected static function booted()
    {
        static::creating(function (PasabuyOrderItem $item) {
            $item->uuid = Str::uuid()->toString();
        });
    }

    protected function isOutOfStock(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->pasabuyScheduleProduct?->product_status === 'out_of_stock',
        );
    }

    public function order()
    {
        return $this->belongsTo(PasabuyOrder::class);
    }

    public function pasabuyScheduleProduct()
    {
        return $this->belongsTo(PasabuyScheduleProduct::class);
    }
}
