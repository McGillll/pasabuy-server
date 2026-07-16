<?php

namespace App\Models;

use EloquentFilter\Filterable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Fillable(['customer_name', 'customer_contact', 'status', 'total_amount', 'payment_status', 'delivery_status'])]
class PasabuyOrder extends Model
{
    use Filterable, HasFactory;

    public const EDITABLE_STATUSES = ['pending'];

    public const LIVE_STATUSES = ['pending', 'confirmed'];

    public const PAYMENT_STATUSES = ['unpaid', 'partially_paid', 'paid'];

    public const DELIVERY_STATUSES = ['not_delivered', 'delivered'];

    protected function casts()
    {
        return [
            'delivered_at' => 'datetime',
            'invoice_sent_at' => 'datetime',
            'last_overdue_reminder_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        static::creating(function (PasabuyOrder $order) {
            $order->uuid = Str::uuid()->toString();
            $order->order_no = static::generateOrderNo();
        });
    }

    protected static function generateOrderNo()
    {
        do {
            $orderNo = sprintf('PSBY-%s-%s', now()->format('Ymd'), Str::upper(Str::random(6)));
        } while (static::where('order_no', $orderNo)->exists());

        return $orderNo;
    }

    public function pasabuySchedule()
    {
        return $this->belongsTo(PasabuySchedule::class);
    }

    public function items()
    {
        return $this->hasMany(PasabuyOrderItem::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payments()
    {
        return $this->hasMany(PasabuyOrderPayment::class, 'order_id');
    }
}
