<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['customer_name', 'customer_contact', 'status', 'total_amount'])]
class Order extends Model
{
    public const EDITABLE_STATUSES = ['pending'];

    public const LIVE_STATUSES = ['pending', 'confirmed'];

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
