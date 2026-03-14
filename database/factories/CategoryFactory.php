<?php

namespace Database\Factories;

use App\Enums\CategoryType;
use App\Models\Household;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'name' => fake()->word() . ' Category',
            'type' => fake()->randomElement([CategoryType::Income, CategoryType::Expense]),
            'icon' => fake()->word(),
            'color' => fake()->hexColor(),
        ];
    }
}
