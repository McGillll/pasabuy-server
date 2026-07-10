<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['pasabuy_schedule_id', 'product_id', 'product_name', 'quantity', 'checked_at', 'additional_quantity', 'return_quantity'])]
class PasabuyScheduleChecklistItem extends Model
{
    protected static function booted()
    {
        static::creating(function (PasabuyScheduleChecklistItem $item) {
            $item->uuid = Str::uuid()->toString();
        });
    }

    protected function casts()
    {
        return [
            'checked_at' => 'datetime',
        ];
    }

    protected function isChecked(): Attribute
    {
        return Attribute::make(get: fn () => $this->checked_at !== null);
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
