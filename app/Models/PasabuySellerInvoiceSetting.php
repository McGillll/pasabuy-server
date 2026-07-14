<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable([
    'markup_threshold_amount',
    'markup_percentage',
    'markup_fixed_amount',
    'due_days',
    'due_date_reference',
    'interest_rate',
    'interest_period',
    'interest_base',
    'is_active',
])]
class PasabuySellerInvoiceSetting extends Model
{
    public const DUE_DATE_REFERENCES = ['delivery', 'schedule_finish'];

    public const INTEREST_PERIODS = ['day', 'week', 'month'];

    public const INTEREST_BASES = ['outstanding_balance', 'full_total'];

    protected static function booted()
    {
        static::creating(function (PasabuySellerInvoiceSetting $setting) {
            $setting->uuid = Str::uuid()->toString();
        });
    }

    protected function casts()
    {
        return [
            'markup_threshold_amount' => 'decimal:2',
            'markup_percentage' => 'decimal:2',
            'markup_fixed_amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
