<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['amount', 'method', 'reference_number', 'notes', 'paid_at'])]
class PasabuyOrderPayment extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::creating(function (PasabuyOrderPayment $payment) {
            $payment->uuid = Str::uuid()->toString();
        });
    }

    protected function casts()
    {
        return [
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function order()
    {
        return $this->belongsTo(PasabuyOrder::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
