<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    protected const CUSTOMERS = [
        ['name' => 'Juan Dela Cruz', 'email' => 'juan@pasabuy.test', 'contact' => '09171234567', 'is_approved' => true],
        ['name' => 'Maria Santos', 'email' => 'maria@pasabuy.test', 'contact' => '09182345678', 'is_approved' => true],
        ['name' => 'Pedro Reyes', 'email' => 'pedro@pasabuy.test', 'contact' => '09193456789', 'is_approved' => true],
        ['name' => 'Ana Garcia', 'email' => 'ana@pasabuy.test', 'contact' => '09204567890', 'is_approved' => false],
    ];

    public function run()
    {
        if (User::where('role', 'customer')->exists()) {
            return;
        }

        foreach (self::CUSTOMERS as $item) {
            $customer = new User;
            $customer->name = $item['name'];
            $customer->email = $item['email'];
            $customer->contact_number = $item['contact'];
            $customer->password = Hash::make('password');
            $customer->role = 'customer';
            $customer->is_approved = $item['is_approved'];
            $customer->save();
        }
    }
}
