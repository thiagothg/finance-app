<?php

declare(strict_types=1);

use App\Enums\AccountType;
use App\Enums\HouseholdMemberRole;
use App\Models\Account;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

uses(RefreshDatabase::class);

function makeHouseholdFor(User $owner, array $members = []): Household
{
    $household = Household::factory()->create(['owner_id' => $owner->id]);

    HouseholdMember::factory()->create([
        'household_id' => $household->id,
        'user_id' => $owner->id,
        'role' => HouseholdMemberRole::Owner,
    ]);

    foreach ($members as $member) {
        HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'user_id' => $member->id,
            'role' => HouseholdMemberRole::Member,
        ]);
    }

    return $household;
}

describe('listAccounts', function (): void {

    it('returns only own accounts when user has no household', function (): void {
        $user = User::factory()->create();
        $own = Account::factory()->create(['user_id' => $user->id]);
        $other = Account::factory()->create(); // belongs to someone else

        $result = (new AccountService)->listAccounts($user);

        expect($result['accounts'])->toHaveCount(1)
            ->and($result['accounts']->first()->id)->toBe($own->id);
    });

    it('returns all household member accounts when user belongs to a household', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        makeHouseholdFor($owner, [$member]);

        Account::factory()->create(['user_id' => $owner->id]);
        Account::factory()->create(['user_id' => $member->id]);
        Account::factory()->create(); // unrelated user

        $result = (new AccountService)->listAccounts($owner);

        expect($result['accounts'])->toHaveCount(2);
    });

    it('calculates total_balance using balance when it is non-zero', function (): void {
        $user = User::factory()->create();
        Account::factory()->create([
            'user_id' => $user->id,
            'balance' => 1000.00,
            'initial_balance' => 500.00, // should be ignored
        ]);
        Account::factory()->create([
            'user_id' => $user->id,
            'balance' => 250.00,
            'initial_balance' => 100.00, // should be ignored
        ]);

        $result = (new AccountService)->listAccounts($user);

        expect($result['total_balance'])->toBe(1250.00);
    });

    it('falls back to initial_balance when balance is zero', function (): void {
        $user = User::factory()->create();
        Account::factory()->create([
            'user_id' => $user->id,
            'balance' => 0,
            'initial_balance' => 800.00,
        ]);

        $result = (new AccountService)->listAccounts($user);

        expect($result['total_balance'])->toBe(800.00);
    });

    it('mixes balance and initial_balance correctly across accounts', function (): void {
        $user = User::factory()->create();

        Account::factory()->create([
            'user_id' => $user->id,
            'balance' => 500.00,
            'initial_balance' => 999.00, // ignored — balance is non-zero
        ]);
        Account::factory()->create([
            'user_id' => $user->id,
            'balance' => 0,
            'initial_balance' => 300.00, // used — balance is zero
        ]);

        $result = (new AccountService)->listAccounts($user);

        expect($result['total_balance'])->toBe(800.00);
    });

    it('rounds total_balance to 2 decimal places', function (): void {
        $user = User::factory()->create();
        Account::factory()->create(['user_id' => $user->id, 'balance' => 100.005]);
        Account::factory()->create(['user_id' => $user->id, 'balance' => 200.004]);

        $result = (new AccountService)->listAccounts($user);

        expect($result['total_balance'])->toBe(round(300.009, 2));
    });

    it('returns total_balance of 0.0 when user has no accounts', function (): void {
        $user = User::factory()->create();

        $result = (new AccountService)->listAccounts($user);

        expect($result['accounts'])->toBeEmpty()
            ->and($result['total_balance'])->toBe(0.0);
    });

    it('eager loads the user relation on each account', function (): void {
        $user = User::factory()->create();
        Account::factory()->create(['user_id' => $user->id]);

        $result = (new AccountService)->listAccounts($user);

        expect($result['accounts']->first()->relationLoaded('user'))->toBeTrue();
    });

});

describe('createAccount', function (): void {

    it('creates an account and assigns the authenticated user id by default', function (): void {
        $user = User::factory()->create();
        $data = [
            'name' => 'Savings',
            'type' => AccountType::Savings->value,
            'balance' => 0,
            'initial_balance' => 1000.00,
            'currency' => 'BRL',
            'bank' => 'Test Bank',
        ];

        $account = (new AccountService)->createAccount($user, $data);

        expect($account->user_id)->toBe($user->id)
            ->and($account->name)->toBe('Savings');
    });

    it('respects an explicit user_id when provided in data', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $data = [
            'user_id' => $member->id,
            'name' => 'Member Wallet',
            'type' => AccountType::Checking->value,
            'balance' => 0,
            'initial_balance' => 0,
            'currency' => 'BRL',
            'bank' => 'Test Bank',
        ];

        $account = (new AccountService)->createAccount($owner, $data);

        expect($account->user_id)->toBe($member->id);
    });

    it('persists the account to the database', function (): void {
        $user = User::factory()->create();

        $account = (new AccountService)->createAccount($user, [
            'name' => 'Checking',
            'type' => AccountType::Checking->value,
            'balance' => 500.00,
            'initial_balance' => 500.00,
            'currency' => 'BRL',
            'bank' => 'Test Bank',
        ]);

        assertDatabaseHas('accounts', ['id' => $account->id]);
    });

    it('eager loads the user relation after creation', function (): void {
        $user = User::factory()->create();
        $account = (new AccountService)->createAccount($user, [
            'name' => 'Test',
            'type' => AccountType::Checking->value,
            'balance' => 0,
            'initial_balance' => 0,
            'currency' => 'BRL',
            'bank' => 'Test Bank',
        ]);

        expect($account->relationLoaded('user'))->toBeTrue();
    });

});

describe('updateAccount', function (): void {

    it('updates the account fields', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

        $updated = (new AccountService)->updateAccount($account, ['name' => 'New Name']);

        expect($updated->name)->toBe('New Name');
        assertDatabaseHas('accounts', ['id' => $account->id, 'name' => 'New Name']);
    });

    it('eager loads the user relation after update', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        $updated = (new AccountService)->updateAccount($account, ['name' => 'Updated']);

        expect($updated->relationLoaded('user'))->toBeTrue();
    });

});

describe('deleteAccount', function (): void {

    it('soft-deletes the account', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        (new AccountService)->deleteAccount($account);

        assertSoftDeleted('accounts', ['id' => $account->id]);
    });

    it('does not permanently remove the record', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        (new AccountService)->deleteAccount($account);

        assertDatabaseHas('accounts', ['id' => $account->id]);
    });

});
