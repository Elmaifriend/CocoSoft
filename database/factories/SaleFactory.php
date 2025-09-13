<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            "product" => fake()->sentence(),
            "description" => fake()->paragraphs(2, true),
            'total_amount' => $this->faker->numberBetween(5000, 30000),
            'canjeado' => false,
        ];
    }
}