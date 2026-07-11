<?php

namespace Database\Factories;

use App\Models\PasabuyOrder;
use App\Models\PasabuySchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PasabuyOrder>
 */
class PasabuyOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'pasabuy_schedule_id' => PasabuySchedule::factory(),
            'user_id' => User::factory(),
            'customer_name' => fake()->name(),
            'customer_contact' => fake()->phoneNumber(),
            'status' => 'pending',
            'total_amount' => 100,
            'payment_status' => 'unpaid',
            'delivery_status' => 'not_delivered',
        ];
    }
}
