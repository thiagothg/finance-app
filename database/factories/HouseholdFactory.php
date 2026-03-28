<?php

namespace Database\Factories;

use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Household>
 */
class HouseholdFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Household',
            'owner_id' => User::factory(),
            'invitation_code' => $this->faker->unique()->numerify('########'),
        ];
    }
}
