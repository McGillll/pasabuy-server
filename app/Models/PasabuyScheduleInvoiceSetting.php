<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'markup_threshold_amount',
    'markup_percentage',
    'markup_fixed_amount',
    'handling_fee',
    'interest_rate',
    'interest_period',
    'interest_base',
    'is_active',
])]
class PasabuyScheduleInvoiceSetting extends Model
{
    use UsesUuid;

    protected function casts()
    {
        return [
            'markup_threshold_amount' => 'decimal:2',
            'markup_percentage' => 'decimal:2',
            'markup_fixed_amount' => 'decimal:2',
            'handling_fee' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function pasabuySchedule()
    {
        return $this->belongsTo(PasabuySchedule::class);
    }

    public function sourceInvoiceSetting()
    {
        return $this->belongsTo(PasabuySellerInvoiceSetting::class, 'source_invoice_setting_id');
    }
}
