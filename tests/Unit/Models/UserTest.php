<?php

use App\Enums\HouseholdMemberRole;
use App\Models\Account;
use App\Models\Category;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\Transaction;
use App\Models\User;

use function Pest\Laravel\assertSoftDeleted;

it('can create a user', function () {
    $user = User::factory()->create();
    expect($user)->toBeInstanceOf(User::class);
});

it('has accounts', function () {
    $user = User::factory()->hasAccounts(3)->create();
    expect($user->accounts)->toHaveCount(3);
});

it('has households', function () {
    $user = User::factory()->hasHouseholds(2, ['owner_id' => null])->create();
    expect($user->households)->toHaveCount(2);
});

describe('User model relations', function (): void {

    it('accounts() returns the user own accounts', function (): void {
        $user = User::factory()->create();
        Account::factory()->count(2)->create(['user_id' => $user->id]);
        Account::factory()->create(); // another user's account

        expect($user->accounts()->count())->toBe(2);
    });

    it('transactions() returns transactions where the user is the spender', function (): void {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 1000]);
        $otherAc = Account::factory()->create(['user_id' => $other->id, 'balance' => 1000]);

        Transaction::factory()->create(['spender_user_id' => $user->id,  'account_id' => $account->id]);
        Transaction::factory()->create(['spender_user_id' => $user->id,  'account_id' => $account->id]);
        Transaction::factory()->create(['spender_user_id' => $other->id, 'account_id' => $otherAc->id]);

        expect($user->transactions()->count())->toBe(2);
    });

    it('categories() returns categories created by the user', function (): void {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Category::factory()->count(3)->create(['user_id' => $user->id]);
        Category::factory()->create(['user_id' => $other->id]);

        expect($user->categories()->count())->toBe(3);
    });

    it('householdMember() returns the single household membership', function (): void {
        $owner = User::factory()->create();
        $household = Household::factory()->create(['owner_id' => $owner->id]);

        HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdMemberRole::Owner,
        ]);

        expect($owner->householdMember)->not->toBeNull()
            ->and($owner->householdMember->user_id)->toBe($owner->id);
    });

    it('households() returns households owned by the user', function (): void {
        $owner = User::factory()->create();
        Household::factory()->count(2)->create(['owner_id' => $owner->id]);

        expect($owner->households()->count())->toBe(2);
    });

    it('household() returns the household the user belongs to via HasOneThrough', function (): void {
        $owner = User::factory()->create();
        $household = Household::factory()->create(['owner_id' => $owner->id]);

        HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdMemberRole::Owner,
        ]);

        expect($owner->household->id)->toBe($household->id);
    });

    it('softDeletes the user correctly', function (): void {
        $user = User::factory()->create();
        $user->delete();

        assertSoftDeleted('users', ['id' => $user->id]);
    });

});
