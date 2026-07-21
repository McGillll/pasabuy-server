<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['pasabuy_schedule_id', 'product_id', 'actual_price', 'product_status', 'price_confirmed_at', 'is_disabled'])]
class PasabuyScheduleProduct extends Model
{
    use UsesUuid;

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
        return $this->belongsTo(PasabuyProduct::class);
    }
}
