<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'user_id' => User::factory(),
            'name' => fake()->unique()->word().' Category',
            'type' => fake()->randomElement([CategoryType::Income, CategoryType::Expense]),
            'budget' => fake()->randomFloat(2, 0, 10000),
            'icon' => fake()->word(),
            'color' => fake()->hexColor(),
        ];
    }
}
