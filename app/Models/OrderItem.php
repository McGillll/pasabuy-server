<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['product_name', 'estimated_price', 'actual_price', 'is_bonus', 'is_out_of_stock', 'quantity', 'subtotal'])]
class OrderItem extends Model
{
    protected static function booted()
    {
        static::creating(function (OrderItem $item) {
            $item->uuid = Str::uuid()->toString();
        });
    }

    protected function casts()
    {
        return [
            'is_bonus' => 'boolean',
            'is_out_of_stock' => 'boolean',
        ];
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
