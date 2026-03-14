<?php

namespace Database\Factories;

use App\Enums\HouseholdMemberRole;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HouseholdMember>
 */
class HouseholdMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'user_id' => User::factory(),
            'role' => fake()->randomElement([HouseholdMemberRole::Owner, HouseholdMemberRole::Member, HouseholdMemberRole::Viewer]),
        ];
    }
}
