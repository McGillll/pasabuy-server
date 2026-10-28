<?php

namespace App\Models;

use App\Models\Concerns\UsesUuid;
use App\ModelFilters\CustomerFilter;
use Database\Factories\UserFactory;
use EloquentFilter\Filterable;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['first_name', 'last_name', 'email', 'password', 'role', 'contact_number', 'messenger_name'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Filterable, HasApiTokens, HasFactory, MustVerifyEmailTrait, Notifiable, SoftDeletes, UsesUuid;

    /**
     * Sanctum token abilities granted per role. A superadmin carries both the admin
     * and customer abilities so it can use either side of the app.
     */
    public const ROLE_ABILITIES = [
        'superadmin' => ['superadmin', 'admin', 'customer'],
        'admin' => ['admin'],
        'customer' => ['customer'],
    ];

    public function modelFilter()
    {
        return $this->provideFilter(CustomerFilter::class);
    }

    public function orders()
    {
        return $this->hasMany(PasabuyOrder::class);
    }

    public function invoiceSetting()
    {
        return $this->hasOne(PasabuySellerInvoiceSetting::class);
    }

    /**
     * Total balance the customer currently owes across their live, unsettled orders.
     * Always 0 for admins since they place no orders of their own.
     */
    public function getReceivable()
    {
        if ($this->role === 'admin') {
            return 0.0;
        }

        return round($this->orders()
            ->whereIn('status', PasabuyOrder::LIVE_STATUSES)
            ->where('payment_status', '!=', 'paid')
            ->with(['payments', 'pasabuySchedule.pasabuyScheduleInvoiceSetting', 'pasabuySchedule.user.invoiceSetting'])
            ->get()
            ->sum(fn ($order) => $order->invoiceBreakdown()['balance_due']), 2);
    }

    public function fullName()
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function isSuperadmin()
    {
        return $this->role === 'superadmin';
    }

    public function abilities()
    {
        return self::ROLE_ABILITIES[$this->role] ?? [];
    }

    public function getIsApprovedAttribute()
    {
        return in_array($this->role, ['admin', 'superadmin'], true) || $this->approved_at !== null;
    }

    protected function casts()
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
