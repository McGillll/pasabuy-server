<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['title', 'description', 'cutoff_at', 'status'])]
class PasabuySchedule extends Model
{
    use Filterable;

    protected static function booted(): void
    {
        static::creating(function (PasabuySchedule $schedule) {
            $schedule->uuid = Str::uuid()->toString();
        });
    }

    protected function casts(): array
    {
        return [
            'cutoff_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scheduleProducts(): HasMany
    {
        return $this->hasMany(PasabuyScheduleProduct::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
