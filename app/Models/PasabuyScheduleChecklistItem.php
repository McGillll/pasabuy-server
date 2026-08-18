<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['pasabuy_schedule_id', 'product_id', 'product_name', 'quantity', 'picked_quantity', 'checked_at', 'additional_quantity', 'return_quantity'])]
class PasabuyScheduleChecklistItem extends Model
{
    use UsesUuid;

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
        return $this->belongsTo(PasabuyProduct::class);
    }
}
