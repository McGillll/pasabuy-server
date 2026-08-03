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
class PasabuySellerInvoiceSetting extends Model
{
    use UsesUuid;

    public const INTEREST_PERIODS = ['day', 'week', 'month'];

    public const INTEREST_BASES = ['outstanding_balance', 'full_total'];

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

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
