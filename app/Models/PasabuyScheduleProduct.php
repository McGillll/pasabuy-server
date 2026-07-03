<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['price', 'max_quantity', 'is_disabled'])]
class PasabuyScheduleProduct extends Model
{
    protected static function booted(): void
    {
        static::creating(function (PasabuyScheduleProduct $scheduleProduct) {
            $scheduleProduct->uuid = Str::uuid()->toString();
        });
    }

    protected function casts(): array
    {
        return [
            'is_disabled' => 'boolean',
        ];
    }

    public function pasabuySchedule(): BelongsTo
    {
        return $this->belongsTo(PasabuySchedule::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
