<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        if (! User::where('email', 'chergil86@yahoo.com')->exists()) {
            User::create([
                'first_name' => 'Chirihil',
                'last_name' => 'Pasabuy',
                'email' => 'chergil86@yahoo.com',
                'password' => 'password123',
                'role' => 'admin',
            ]);
        }

        if (! User::where('email', 'superadmin@pasabuy.test')->exists()) {
            $superadmin = User::create([
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'superadmin@pasabuy.test',
                'password' => 'password123',
                'role' => 'superadmin',
            ]);
            $superadmin->markEmailAsVerified();
        }
    }
}
