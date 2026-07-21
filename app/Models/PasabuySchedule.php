<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'description', 'pasabuy_schedule_date', 'status'])]
class PasabuySchedule extends Model
{
    use Filterable, HasFactory, UsesUuid;

    public const ALLOWED_STATUS_TRANSITIONS = [
        'open' => ['closed'],
        'closed' => ['open', 'finished'],
        'finished' => [],
    ];

    protected function casts()
    {
        return [
            'pasabuy_schedule_date' => 'date',
            'finished_at' => 'datetime',
        ];
    }

    protected function isOpen(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'open' && (! $this->pasabuy_schedule_date || $this->pasabuy_schedule_date->isFuture()),
        );
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
        return $this->hasMany(PasabuyOrder::class);
    }
}
