<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Category;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create 10 Users
        $users = User::factory(10)->create();

        // Create 5 Households, each owned by a random user
        $households = Household::factory(5)->recycle($users)->create();

        // For each household, create members, categories, accounts, and transactions
        foreach ($households as $household) {
            // Include the owner as a member
            HouseholdMember::factory()->create([
                'household_id' => $household->id,
                'user_id' => $household->owner_id,
                'role' => \App\Enums\HouseholdMemberRole::Owner,
            ]);

            // Add 1-3 random users as other members
            $members = $users->where('id', '!=', $household->owner_id)->random(rand(1, 3));
            foreach ($members as $member) {
                HouseholdMember::factory()->create([
                    'household_id' => $household->id,
                    'user_id' => $member->id,
                    'role' => fake()->randomElement([\App\Enums\HouseholdMemberRole::Member, \App\Enums\HouseholdMemberRole::Viewer]),
                ]);
            }

            // Create categories for the household
            $categories = Category::factory(5)->create([
                'household_id' => $household->id,
            ]);

            // Create accounts for users in this household (using random members)
            $allHouseholdUsers = $household->members()->pluck('user_id');
            $accounts = collect();
            
            foreach ($allHouseholdUsers as $userId) {
                $accounts = $accounts->merge(
                    Account::factory(2)->create([
                        'user_id' => $userId,
                    ])
                );
            }

            // Create transactions for each account
            foreach ($accounts as $account) {
                Transaction::factory(10)->create([
                    'account_id' => $account->id,
                    'category_id' => $categories->random()->id,
                    'spender_user_id' => $account->user_id, // assuming account owner spent it
                ]);
            }
        }
    }
}
