<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['customer_name', 'customer_contact', 'status', 'total_amount'])]
class Order extends Model
{
    protected static function booted()
    {
        static::creating(function (Order $order) {
            $order->uuid = Str::uuid()->toString();
        });
    }

    public function pasabuySchedule()
    {
        return $this->belongsTo(PasabuySchedule::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
