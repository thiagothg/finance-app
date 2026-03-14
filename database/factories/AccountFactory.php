<?php

namespace Database\Factories;

use App\Enums\AccountType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->word() . ' Account',
            'type' => fake()->randomElement([AccountType::Checking, AccountType::Savings, AccountType::Cash]),
            'initial_balance' => fake()->randomFloat(2, 0, 10000),
            'currency' => 'BRL',
        ];
    }
}
