<?php

use App\Enums\AccountType;
use App\Enums\HouseholdMemberRole;
use App\Models\Account;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\getJson;

it('requires authentication for accounts endpoints', function () {
    $response = getJson('/api/v1/accounts');
    $response->assertUnauthorized();
});

it('lists an authenticated users accounts and calculates total balance', function () {
    /** @var Authenticatable $user */
    $user = User::factory()->create();

    // Create an account with balance 0, should sum initial_balance
    Account::factory()->create([
        'user_id' => $user->id,
        'initial_balance' => 500.00,
        'balance' => 0.00,
    ]);

    // Create an account with balance > 0, should sum balance
    Account::factory()->create([
        'user_id' => $user->id,
        'initial_balance' => 100.00,
        'balance' => 1000.00,
    ]);

    actingAs($user)
        ->getJson('/api/v1/accounts')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total_balance', 1500);
});

it('lists accounts of other household members', function () {
    $household = Household::factory()->create();
    /** @var Authenticatable $owner */
    $owner = User::factory()->create();
    $member = User::factory()->create();

    // Attach to household
    HouseholdMember::factory()->create(['household_id' => $household->id, 'user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);
    HouseholdMember::factory()->create(['household_id' => $household->id, 'user_id' => $member->id, 'role' => HouseholdMemberRole::Member]);

    Account::factory()->create(['user_id' => $owner->id, 'balance' => 100]);
    Account::factory()->create(['user_id' => $member->id, 'balance' => 200]);

    actingAs($owner)
        ->getJson('/api/v1/accounts')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total_balance', 300);
});

it('can create an account', function () {
    /** @var Authenticatable $user */
    $user = User::factory()->create();

    $payload = [
        'name' => 'Main Checking',
        'type' => AccountType::Checking->value,
        'initial_balance' => 500.50,
        'currency' => 'BRL',
        'bank' => 'Nubank',
    ];

    actingAs($user)
        ->postJson('/api/v1/accounts', $payload)
        ->assertCreated()
        ->assertJsonPath('data.name', 'Main Checking')
        ->assertJsonPath('data.user_name', $user->name);

    assertDatabaseHas('accounts', [
        'user_id' => $user->id,
        'name' => 'Main Checking',
        'bank' => 'Nubank',
    ]);
});

it('enforces unique constraints on name bank and type scoped to user', function () {
    /** @var Authenticatable $user */
    $user = User::factory()->create();

    Account::factory()->create([
        'user_id' => $user->id,
        'name' => 'Duplicated',
        'type' => AccountType::Savings->value,
        'bank' => 'Itau',
    ]);

    $payload = [
        'name' => 'Duplicated',
        'type' => AccountType::Savings->value,
        'initial_balance' => 100,
        'currency' => 'BRL',
        'bank' => 'Itau',
    ];

    actingAs($user)
        ->postJson('/api/v1/accounts', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

it('allows updating own account', function () {
    /** @var Authenticatable $user */
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id, 'name' => 'Old Name', 'type' => AccountType::Cash->value, 'bank' => 'Cash']);

    actingAs($user)
        ->putJson("/api/v1/accounts/{$account->id}", [
            'name' => 'New Name',
            'type' => AccountType::Cash->value,
            'initial_balance' => 10,
            'currency' => 'BRL',
            'bank' => 'Cash',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name');
});

it('allows household owner to update member account', function () {
    $household = Household::factory()->create();

    /** @var Authenticatable $owner */
    $owner = User::factory()->create();
    $member = User::factory()->create();

    HouseholdMember::factory()->create(['household_id' => $household->id, 'user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);
    HouseholdMember::factory()->create(['household_id' => $household->id, 'user_id' => $member->id, 'role' => HouseholdMemberRole::Member]);

    $memberAccount = Account::factory()->create(['user_id' => $member->id, 'name' => 'Member Acct', 'type' => AccountType::Checking->value, 'bank' => 'Bank']);

    // Owner edits member account
    actingAs($owner)
        ->putJson("/api/v1/accounts/{$memberAccount->id}", [
            'name' => 'Edited by Owner',
            'type' => AccountType::Checking->value,
            'initial_balance' => 0,
            'currency' => 'BRL',
            'bank' => 'Bank',
            'user_id' => $member->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Edited by Owner');
});

it('forbids normal household member from updating another member account', function () {
    $household = Household::factory()->create();
    /** @var Authenticatable $member1 */
    $member1 = User::factory()->create();
    /** @var Authenticatable $member2 */
    $member2 = User::factory()->create();

    HouseholdMember::factory()->create(['household_id' => $household->id, 'user_id' => $member1->id, 'role' => HouseholdMemberRole::Member]);
    HouseholdMember::factory()->create(['household_id' => $household->id, 'user_id' => $member2->id, 'role' => HouseholdMemberRole::Member]);

    $member2Account = Account::factory()->create(['user_id' => $member2->id]);

    actingAs($member1)
        ->putJson("/api/v1/accounts/{$member2Account->id}", [
            'name' => 'Hacked',
            'type' => AccountType::Checking->value,
            'initial_balance' => 0,
            'currency' => 'BRL',
            'bank' => 'Bank',
        ])
        ->assertForbidden();
});

it('can delete an account', function () {
    /** @var Authenticatable $user */
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->deleteJson("/api/v1/accounts/{$account->id}")
        ->assertNoContent();

    assertSoftDeleted('accounts', ['id' => $account->id]);
});
