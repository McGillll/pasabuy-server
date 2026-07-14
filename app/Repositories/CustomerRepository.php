<?php

namespace App\Repositories;

use App\Interfaces\Customer\CustomerRepositoryInterface;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class CustomerRepository implements CustomerRepositoryInterface
{
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
        $customer->save();

        return $customer;
    }

    public function approve(string $uuid)
    {
        $customer = $this->findByUuid($uuid);
        $customer->approved_at = Carbon::now();
        $customer->save();

        return $customer;
    }

    public function reject(string $uuid)
    {
        $customer = $this->findByUuid($uuid);
        $customer->approved_at = null;
        $customer->save();

        return $customer;
    }

    public function orders(string $uuid)
    {
        $customer = $this->findByUuid($uuid);

        return $customer->orders()->with('items.pasabuyScheduleProduct.product', 'pasabuySchedule')->latest()->get();
    }
}
