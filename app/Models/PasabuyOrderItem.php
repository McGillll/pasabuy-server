<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['product_name', 'estimated_price', 'actual_price', 'quantity', 'bonus_quantity', 'subtotal'])]
class PasabuyOrderItem extends Model
{
    use UsesUuid;

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
