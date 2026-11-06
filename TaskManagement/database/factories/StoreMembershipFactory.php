<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\StoreMembership>
 */
class StoreMembershipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'user_id' => User::factory(),
            'role' => fake()->randomElement(['manager', 'staff']),
        ];
    }
}