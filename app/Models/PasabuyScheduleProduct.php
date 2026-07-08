<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['actual_price', 'promo_status', 'price_confirmed_at', 'is_disabled'])]
class PasabuyScheduleProduct extends Model
{
    protected static function booted()
    {
        static::creating(function (PasabuyScheduleProduct $scheduleProduct) {
            $scheduleProduct->uuid = Str::uuid()->toString();
        });
    }

    protected function casts()
    {
        return [
            'is_disabled' => 'boolean',
            'price_confirmed_at' => 'datetime',
        ];
    }

    public function pasabuySchedule()
    {
        return $this->belongsTo(PasabuySchedule::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
