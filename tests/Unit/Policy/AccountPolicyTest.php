<?php

declare(strict_types=1);

use App\Enums\HouseholdMemberRole;
use App\Models\Account;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\User;
use App\Policies\AccountPolicy;

use function Pest\Laravel\actingAs;

/**
 * Create a household owned by $owner with $members as Member-role users.
 *
 * @param  User[]  $members
 */
function createHousehold(User $owner, array $members = []): Household
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

    $owner->setRelation('household', $household);

    return $household;
}

$ownAccountCases = [
    'own account' => [true,  fn () => ['same' => true,  'owner' => false]],
    'household owner on member account' => [true,  fn () => ['same' => false, 'owner' => true]],
    'household member on owner account' => [false, fn () => ['same' => false, 'owner' => false]],
    'stranger (no shared household)' => [false, fn () => ['same' => false, 'owner' => false]],
];

describe('viewAny', function (): void {

    it('always returns true for any authenticated user', function (): void {
        $user = User::factory()->create();

        expect((new AccountPolicy)->viewAny($user))->toBeTrue();
    });

});

describe('create', function (): void {

    it('always returns true for any authenticated user', function (): void {
        $user = User::factory()->create();

        expect((new AccountPolicy)->create($user))->toBeTrue();
    });

});

describe('view', function (): void {

    it('allows a user to view their own account', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        expect((new AccountPolicy)->view($user, $account))->toBeTrue();
    });

    it('allows a household member to view another member account', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        createHousehold($owner, [$member]);
        $member->setRelation('household', $owner->household);

        $account = Account::factory()->create(['user_id' => $owner->id]);

        expect((new AccountPolicy)->view($member, $account))->toBeTrue();
    });

    it('denies a user with no household from viewing another user account', function (): void {
        $user = User::factory()->create(); // no household relation
        $other = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $other->id]);

        // Ensure household relation is null (no household)
        $user->setRelation('household', null);

        expect((new AccountPolicy)->view($user, $account))->toBeFalse();
    });

    it('denies a user from viewing an account belonging to a different household', function (): void {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        createHousehold($ownerA);
        createHousehold($ownerB);

        $account = Account::factory()->create(['user_id' => $ownerB->id]);

        // ownerA is in a different household — target not in members
        expect((new AccountPolicy)->view($ownerA, $account))->toBeFalse();
    });

});

describe('update', function (): void {

    it('allows the account owner to update their own account', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        expect((new AccountPolicy)->update($user, $account))->toBeTrue();
    });

    it('allows a household owner to update a member account', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        createHousehold($owner, [$member]);

        $account = Account::factory()->create(['user_id' => $member->id]);

        expect((new AccountPolicy)->update($owner, $account))->toBeTrue();
    });

    it('denies a household member from updating another member account', function (): void {
        $owner = User::factory()->create();
        $memberA = User::factory()->create();
        $memberB = User::factory()->create();
        createHousehold($owner, [$memberA, $memberB]);
        $memberA->setRelation('household', $owner->household);

        $account = Account::factory()->create(['user_id' => $memberB->id]);

        expect((new AccountPolicy)->update($memberA, $account))->toBeFalse();
    });

    it('denies a user with no household from updating another user account', function (): void {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $other->id]);

        $user->setRelation('household', null);

        expect((new AccountPolicy)->update($user, $account))->toBeFalse();
    });

    it('denies a household owner from updating an account outside their household', function (): void {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        createHousehold($ownerA);
        createHousehold($ownerB);

        $account = Account::factory()->create(['user_id' => $ownerB->id]);

        // ownerB is not in ownerA's household → isHouseholdOwner returns false
        expect((new AccountPolicy)->update($ownerA, $account))->toBeFalse();
    });

});

describe('delete', function (): void {

    it('allows the account owner to delete their own account', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        expect((new AccountPolicy)->delete($user, $account))->toBeTrue();
    });

    it('allows a household owner to delete a member account', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        createHousehold($owner, [$member]);

        $account = Account::factory()->create(['user_id' => $member->id]);

        expect((new AccountPolicy)->delete($owner, $account))->toBeTrue();
    });

    it('denies a household member from deleting another member account', function (): void {
        $owner = User::factory()->create();
        $memberA = User::factory()->create();
        $memberB = User::factory()->create();
        createHousehold($owner, [$memberA, $memberB]);
        $memberA->setRelation('household', $owner->household);

        $account = Account::factory()->create(['user_id' => $memberB->id]);

        expect((new AccountPolicy)->delete($memberA, $account))->toBeFalse();
    });

    it('denies a user with no household from deleting another user account', function (): void {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $other->id]);

        $user->setRelation('household', null);

        expect((new AccountPolicy)->delete($user, $account))->toBeFalse();
    });

});

describe('restore', function (): void {

    it('allows the account owner to restore their own account', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        expect((new AccountPolicy)->restore($user, $account))->toBeTrue();
    });

    it('allows a household owner to restore a member account', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        createHousehold($owner, [$member]);

        $account = Account::factory()->create(['user_id' => $member->id]);

        expect((new AccountPolicy)->restore($owner, $account))->toBeTrue();
    });

    it('denies a household member from restoring another member account', function (): void {
        $owner = User::factory()->create();
        $memberA = User::factory()->create();
        $memberB = User::factory()->create();
        createHousehold($owner, [$memberA, $memberB]);
        $memberA->setRelation('household', $owner->household);

        $account = Account::factory()->create(['user_id' => $memberB->id]);

        expect((new AccountPolicy)->restore($memberA, $account))->toBeFalse();
    });

    it('denies a user with no household from restoring another user account', function (): void {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $other->id]);

        $user->setRelation('household', null);

        expect((new AccountPolicy)->restore($user, $account))->toBeFalse();
    });

});

describe('forceDelete', function (): void {

    it('allows the account owner to force-delete their own account', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id]);

        expect((new AccountPolicy)->forceDelete($user, $account))->toBeTrue();
    });

    it('allows a household owner to force-delete a member account', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        createHousehold($owner, [$member]);

        $account = Account::factory()->create(['user_id' => $member->id]);

        expect((new AccountPolicy)->forceDelete($owner, $account))->toBeTrue();
    });

    it('denies a household member from force-deleting another member account', function (): void {
        $owner = User::factory()->create();
        $memberA = User::factory()->create();
        $memberB = User::factory()->create();
        createHousehold($owner, [$memberA, $memberB]);
        $memberA->setRelation('household', $owner->household);

        $account = Account::factory()->create(['user_id' => $memberB->id]);

        expect((new AccountPolicy)->forceDelete($memberA, $account))->toBeFalse();
    });

    it('denies a user with no household from force-deleting another user account', function (): void {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $other->id]);

        $user->setRelation('household', null);

        expect((new AccountPolicy)->forceDelete($user, $account))->toBeFalse();
    });

});

describe('account policy via HTTP', function (): void {

    it('returns 403 when a member tries to delete another member account via API', function (): void {
        $owner = User::factory()->create();

        $memberA = User::factory()->create();
        $memberB = User::factory()->create();
        createHousehold($owner, [$memberA, $memberB]);

        $account = Account::factory()->create(['user_id' => $memberB->id]);

        actingAs($memberA)
            ->deleteJson("/api/v1/accounts/{$account->id}")
            ->assertForbidden();
    });

    it('returns 403 when a stranger tries to view an account via API', function (): void {
        $owner = User::factory()->create();

        $stranger = User::factory()->create();

        $account = Account::factory()->create(['user_id' => $owner->id]);

        actingAs($stranger)
            ->getJson("/api/v1/accounts/{$account->id}")
            ->assertForbidden();
    });

    it('returns 200 when a household owner views a member account via API', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        createHousehold($owner, [$member]);

        $account = Account::factory()->create(['user_id' => $member->id]);

        actingAs($owner)
            ->getJson("/api/v1/accounts/{$account->id}")
            ->assertOk();
    });

});
