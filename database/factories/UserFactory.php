<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username' => fake()->unique()->lexify('user_????????'),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => 'USER',
            'status' => 'ACTIVE',
            'birth_date' => fake()->dateTimeBetween('-40 years', '-18 years')->format('Y-m-d'),
            'email_verified_at' => now(),
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => 'ADMIN']);
    }
}
