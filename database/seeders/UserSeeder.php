<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        if (User::where('email', 'admin@pasabuy.test')->exists()) {
            return;
        }

        User::create([
            'first_name' => 'Chirihil',
            'last_name' => 'Pasabuy',
            'email' => 'chergil86@yahoo.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
    }
}
