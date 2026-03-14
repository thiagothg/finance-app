<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Household>
 */
class HouseholdFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' Household',
            'owner_id' => User::factory(),
        ];
    }
}
