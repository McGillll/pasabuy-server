<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['product_name', 'product_price', 'quantity', 'subtotal'])]
class OrderItem extends Model
{
    protected static function booted(): void
    {
        static::creating(function (OrderItem $item) {
            $item->uuid = Str::uuid()->toString();
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
