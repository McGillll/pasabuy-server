<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['product_name', 'quantity', 'is_checked', 'checked_at', 'additional_quantity', 'return_quantity'])]
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
            'is_checked' => 'boolean',
            'checked_at' => 'datetime',
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
