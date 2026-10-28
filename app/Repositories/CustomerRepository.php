<?php

namespace App\Repositories;

use App\Interfaces\Customer\CustomerRepositoryInterface;
use App\Models\PasabuyOrder;
use App\Models\User;
use App\Traits\InteractsWithCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class CustomerRepository implements CustomerRepositoryInterface
{
    use InteractsWithCache;

    public function all(object $payload)
    {
        return User::where('role', 'customer')
            ->filter((array) $payload)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate($payload->per_page ?? 15);
    }

    public function findByUuid(string $uuid)
    {
        return User::where('role', 'customer')->where('uuid', $uuid)->first();
    }

    public function findByEmail(string $email)
    {
        return User::where('role', 'customer')->where('email', $email)->first();
    }

    public function create(object $payload)
    {
        $customer = new User;
        $customer->first_name = $payload->first_name;
        $customer->last_name = $payload->last_name;
        $customer->email = $payload->email;
        $customer->contact_number = $payload->contact_number ?? null;
        $customer->messenger_name = $payload->messenger_name ?? null;
        $customer->password = Hash::make($payload->password);
        $customer->role = 'customer';
        $customer->terms_accepted_at = Carbon::now();
        $customer->save();

        $this->bumpCacheVersion('customers');

        return $customer;
    }

    public function approve(string $uuid)
    {
        $customer = $this->findByUuid($uuid);
        $customer->approved_at = Carbon::now();
        $customer->save();

        $this->bumpCacheVersion('customers');

        return $customer;
    }

    public function reject(string $uuid)
    {
        $customer = $this->findByUuid($uuid);
        $customer->approved_at = null;
        $customer->save();

        $this->bumpCacheVersion('customers');

        return $customer;
    }

    public function orders(string $uuid)
    {
        $customer = $this->findByUuid($uuid);

        return $customer->orders()->with('items.pasabuyScheduleProduct.product', 'pasabuySchedule')->latest()->get();
    }

    public function topBuyers(object $payload)
    {
        $year = $payload->year ?? now()->year;
        $month = $payload->month ?? now()->month;

        return PasabuyOrder::selectRaw('users.id, users.uuid, users.first_name, users.last_name, SUM(pasabuy_orders.total_amount) as total_spend, COUNT(*) as order_count')
            ->join('users', 'users.id', '=', 'pasabuy_orders.user_id')
            ->whereHas('pasabuySchedule', fn ($query) => $query->when($payload->seller_id ?? null, fn ($q2, $sellerId) => $q2->where('user_id', $sellerId))
                ->whereYear('pasabuy_schedule_date', $year)
                ->whereMonth('pasabuy_schedule_date', $month))
            ->whereIn('status', PasabuyOrder::LIVE_STATUSES)
            ->groupBy('users.id', 'users.uuid', 'users.first_name', 'users.last_name')
            ->orderByDesc('total_spend')
            ->limit($payload->limit ?? 5)
            ->get();
    }
}
