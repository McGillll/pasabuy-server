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

        User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@pasabuy.test',
            'password' => 'password',
            'role' => 'admin',
        ]);
    }
}
