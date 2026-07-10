<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
            // ProductCategorySeeder::class,
            // ProductSeeder::class,
            // CustomerSeeder::class,
            // PasabuyScheduleSeeder::class,
            // OrderSeeder::class,
        ]);
    }
}
