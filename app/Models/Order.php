<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['customer_name', 'customer_contact', 'status', 'total_amount'])]
class Order extends Model
{
    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            $order->uuid = Str::uuid()->toString();
        });
    }

    public function pasabuySchedule(): BelongsTo
    {
        return $this->belongsTo(PasabuySchedule::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
