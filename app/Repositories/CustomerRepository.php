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
            ->orderBy('name')
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
        $customer->name = $payload->name;
        $customer->email = $payload->email;
        $customer->contact_number = $payload->contact_number ?? null;
        $customer->facebook_url = $payload->facebook_url ?? null;
        $customer->password = Hash::make($payload->password);
        $customer->role = 'customer';
        $customer->is_approved = false;
        $customer->save();

        return $customer;
    }

    public function approve(string $uuid)
    {
        $customer = $this->findByUuid($uuid);
        $customer->is_approved = true;
        $customer->approved_at = Carbon::now();
        $customer->save();

        return $customer;
    }

    public function reject(string $uuid)
    {
        $customer = $this->findByUuid($uuid);
        $customer->is_approved = false;
        $customer->approved_at = null;
        $customer->save();

        return $customer;
    }
}
