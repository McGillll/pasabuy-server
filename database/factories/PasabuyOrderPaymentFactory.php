<?php

namespace Database\Factories;

use App\Models\PasabuyOrder;
use App\Models\PasabuyOrderPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PasabuyOrderPayment>
 */
class PasabuyOrderPaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'order_id' => PasabuyOrder::factory(),
            'recorded_by_user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 10, 100),
            'method' => 'cash',
            'reference_number' => null,
            'notes' => null,
            'paid_at' => now(),
        ];
    }
}
