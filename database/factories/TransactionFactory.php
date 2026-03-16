<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'category_id' => Category::factory(),
            'spender_user_id' => User::factory(),
            'amount' => fake()->randomFloat(2, 1, 1000),
            'type' => fake()->randomElement([TransactionType::Income, TransactionType::Expense, TransactionType::Transfer]),
            'description' => fake()->sentence(),
            'transaction_at' => fake()->dateTimeThisYear(),
            'to_account_id' => null,
        ];
    }
}
