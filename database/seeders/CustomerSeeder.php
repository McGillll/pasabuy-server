<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    protected const CUSTOMERS = [
        ['first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'email' => 'juan@pasabuy.test', 'contact' => '09171234567', 'messenger' => 'juan.delacruz', 'approved' => true],
        ['first_name' => 'Maria', 'last_name' => 'Santos', 'email' => 'maria@pasabuy.test', 'contact' => '09182345678', 'messenger' => 'maria.santos', 'approved' => true],
        ['first_name' => 'Pedro', 'last_name' => 'Reyes', 'email' => 'pedro@pasabuy.test', 'contact' => '09193456789', 'messenger' => 'pedro.reyes', 'approved' => true],
        ['first_name' => 'Ana', 'last_name' => 'Garcia', 'email' => 'ana@pasabuy.test', 'contact' => '09204567890', 'messenger' => 'ana.garcia', 'approved' => false],
    ];

    public function run()
    {
        if (User::where('role', 'customer')->exists()) {
            return;
        }

        foreach (self::CUSTOMERS as $item) {
            $customer = new User;
            $customer->first_name = $item['first_name'];
            $customer->last_name = $item['last_name'];
            $customer->email = $item['email'];
            $customer->contact_number = $item['contact'];
            $customer->messenger_name = $item['messenger'];
            $customer->password = Hash::make('password');
            $customer->role = 'customer';
            $customer->approved_at = $item['approved'] ? Carbon::now() : null;
            $customer->save();
        }
    }
}
