<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['title', 'description', 'cutoff_at', 'status'])]
class PasabuySchedule extends Model
{
    use Filterable;

    public const ALLOWED_STATUS_TRANSITIONS = [
        'draft' => ['open'],
        'open' => ['closed'],
        'closed' => ['open'],
    ];

    protected static function booted()
    {
        static::creating(function (PasabuySchedule $schedule) {
            $schedule->uuid = Str::uuid()->toString();
        });
    }

    protected function casts()
    {
        return [
            'cutoff_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scheduleProducts()
    {
        return $this->hasMany(PasabuyScheduleProduct::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
