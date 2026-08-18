<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'description', 'pasabuy_schedule_date', 'status', 'due_date'])]
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
            'due_date' => 'date',
        ];
    }

    protected function isOpen(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'open',
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

    public function pasabuyScheduleInvoiceSetting()
    {
        return $this->hasOne(PasabuyScheduleInvoiceSetting::class);
    }
}
