<?php

declare(strict_types=1);

use App\Enums\HouseholdMemberRole;
use App\Models\Account;
use App\Models\Category;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\Transaction;
use App\Models\User;
use App\Services\HouseholdService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

function makeHousehold(User $owner, array $extras = []): Household
{
    $household = Household::factory()->create(['owner_id' => $owner->id]);

    HouseholdMember::factory()->create([
        'household_id' => $household->id,
        'user_id' => $owner->id,
        'role' => HouseholdMemberRole::Owner,
    ]);

    foreach ($extras as ['user' => $user, 'role' => $role]) {
        HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);
    }

    $owner->load('household');

    return $household;
}

describe('getByUser', function (): void {

    // Line 33 — user has no household
    it('returns an empty collection when user has no household', function (): void {
        $user = User::factory()->create();

        $result = (new HouseholdService)->getByUser($user);

        expect($result)->toBeEmpty();
    });

    it('returns a collection with the household when user belongs to one', function (): void {
        $owner = User::factory()->create();
        makeHousehold($owner);

        $result = (new HouseholdService)->getByUser($owner);

        expect($result)->toHaveCount(1);
    });

    it('attaches total_spend 0.0 when members have no transactions', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        makeHousehold($owner, [['user' => $member, 'role' => HouseholdMemberRole::Member]]);

        $result = (new HouseholdService)->getByUser($owner);

        $result->first()->members->each(function ($m): void {
            expect($m->total_spend)->toBe(0.0);
        });
    });

    it('calculates total_spend per member from transactions', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $household = makeHousehold($owner, [['user' => $member, 'role' => HouseholdMemberRole::Member]]);
        $owner->load('household');

        $account = Account::factory()->create(['user_id' => $owner->id, 'balance' => 1000]);
        $category = Category::factory()->create([
            'household_id' => $household->id,
            'user_id' => $owner->id,
        ]);

        Transaction::factory()->create([
            'spender_user_id' => $owner->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'amount' => 250.00,
        ]);

        $result = (new HouseholdService)->getByUser($owner);
        $ownerMember = $result->first()->members->firstWhere('user_id', $owner->id);

        expect($ownerMember->total_spend)->toBe(250.0);
    });

    it('loads members_count and members.user relations', function (): void {
        $owner = User::factory()->create();
        makeHousehold($owner);

        $result = (new HouseholdService)->getByUser($owner);
        $household = $result->first();

        expect($household->members_count)->toBe(1)
            ->and($household->members->first()->relationLoaded('user'))->toBeTrue();
    });

});

describe('upsertName', function (): void {

    it('creates a household and adds the user as Owner when no household is passed', function (): void {
        $user = User::factory()->create();

        $household = (new HouseholdService)->upsertName($user, 'Smith Family');

        expect($household->name)->toBe('Smith Family')
            ->and($household->owner_id)->toBe($user->id);

        assertDatabaseHas('household_members', [
            'user_id' => $user->id,
            'role' => HouseholdMemberRole::Owner->value,
        ]);
    });

    it('throws ConflictHttpException when creating a household for a user already in one', function (): void {
        $owner = User::factory()->create();
        makeHousehold($owner);

        expect(fn () => (new HouseholdService)->upsertName($owner, 'Another Family'))
            ->toThrow(ConflictHttpException::class, 'User is already part of a household.');
    });

    it('updates the household name when an existing household is passed', function (): void {
        $owner = User::factory()->create();
        $household = makeHousehold($owner);

        $updated = (new HouseholdService)->upsertName($owner, 'Updated Name', $household);

        expect($updated->name)->toBe('Updated Name');
    });

    it('allows a Member to update the household name', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $household = makeHousehold($owner, [['user' => $member, 'role' => HouseholdMemberRole::Member]]);
        $member->load('household');

        $updated = (new HouseholdService)->upsertName($member, 'Member Renamed', $household);

        expect($updated->name)->toBe('Member Renamed');
    });

    // Lines 106–108 — Viewer tries to update
    it('throws AccessDeniedHttpException when a Viewer tries to update the household name', function (): void {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $household = makeHousehold($owner, [['user' => $viewer, 'role' => HouseholdMemberRole::Viewer]]);

        expect(fn () => (new HouseholdService)->upsertName($viewer, 'Hacked Name', $household))
            ->toThrow(AccessDeniedHttpException::class, 'You do not have permission to edit this household.');
    });

    it('throws AccessDeniedHttpException when a non-member tries to update', function (): void {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $household = makeHousehold($owner);

        expect(fn () => (new HouseholdService)->upsertName($stranger, 'Stolen Name', $household))
            ->toThrow(AccessDeniedHttpException::class);
    });

});

describe('listMembers', function (): void {

    it('returns all members with the user relation loaded', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $household = makeHousehold($owner, [['user' => $member, 'role' => HouseholdMemberRole::Member]]);

        $members = (new HouseholdService)->listMembers($household);

        expect($members)->toHaveCount(2)
            ->and($members->first()->relationLoaded('user'))->toBeTrue();
    });

});

describe('addMember', function (): void {

    it('allows an Owner to add a new member', function (): void {
        $owner = User::factory()->create();
        $newUser = User::factory()->create();
        $household = makeHousehold($owner);

        (new HouseholdService)->addMember($household, $owner, $newUser, HouseholdMemberRole::Member);

        assertDatabaseHas('household_members', [
            'household_id' => $household->id,
            'user_id' => $newUser->id,
            'role' => HouseholdMemberRole::Member->value,
        ]);
    });

    it('allows a Member to add a new member', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $newUser = User::factory()->create();
        $household = makeHousehold($owner, [['user' => $member, 'role' => HouseholdMemberRole::Member]]);

        (new HouseholdService)->addMember($household, $member, $newUser, HouseholdMemberRole::Member);

        assertDatabaseHas('household_members', [
            'household_id' => $household->id,
            'user_id' => $newUser->id,
        ]);
    });

    // Line 150 — Viewer as actor
    it('throws AccessDeniedHttpException when a Viewer tries to add a member', function (): void {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $newUser = User::factory()->create();
        $household = makeHousehold($owner, [['user' => $viewer, 'role' => HouseholdMemberRole::Viewer]]);

        expect(fn () => (new HouseholdService)->addMember($household, $viewer, $newUser, HouseholdMemberRole::Member))
            ->toThrow(AccessDeniedHttpException::class, 'You do not have permission to add members.');
    });

    // Line 158 — userToAdd already in a household
    it('throws ConflictHttpException when the user to add already belongs to a household', function (): void {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $household = makeHousehold($owner);
        makeHousehold($otherOwner);

        expect(fn () => (new HouseholdService)->addMember($household, $owner, $otherOwner, HouseholdMemberRole::Member))
            ->toThrow(ConflictHttpException::class, 'User is already part of a household.');
    });

    it('throws AccessDeniedHttpException when a non-member tries to add', function (): void {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $newUser = User::factory()->create();
        $household = makeHousehold($owner);

        expect(fn () => (new HouseholdService)->addMember($household, $stranger, $newUser, HouseholdMemberRole::Member))
            ->toThrow(AccessDeniedHttpException::class);
    });

});

describe('removeMember', function (): void {

    it('allows an Owner to remove a Member', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $household = makeHousehold($owner, [['user' => $member, 'role' => HouseholdMemberRole::Member]]);

        (new HouseholdService)->removeMember($household, $owner, $member);

        assertDatabaseMissing('household_members', [
            'household_id' => $household->id,
            'user_id' => $member->id,
        ]);
    });

    it('allows a user to remove themselves', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $household = makeHousehold($owner, [['user' => $member, 'role' => HouseholdMemberRole::Member]]);

        (new HouseholdService)->removeMember($household, $member, $member);

        assertDatabaseMissing('household_members', [
            'household_id' => $household->id,
            'user_id' => $member->id,
        ]);
    });

    it('throws AccessDeniedHttpException when a Viewer tries to remove a member', function (): void {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $target = User::factory()->create();
        $household = makeHousehold($owner, [
            ['user' => $viewer, 'role' => HouseholdMemberRole::Viewer],
            ['user' => $target, 'role' => HouseholdMemberRole::Member],
        ]);

        expect(fn () => (new HouseholdService)->removeMember($household, $viewer, $target))
            ->toThrow(AccessDeniedHttpException::class, 'You do not have permission to remove members.');
    });

    it('throws NotFoundHttpException when the user to remove is not a member', function (): void {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $household = makeHousehold($owner);

        expect(fn () => (new HouseholdService)->removeMember($household, $owner, $stranger))
            ->toThrow(NotFoundHttpException::class, 'User is not a member of this household.');
    });

    it('throws AccessDeniedHttpException when trying to remove the Owner', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $household = makeHousehold($owner, [['user' => $member, 'role' => HouseholdMemberRole::Member]]);

        expect(fn () => (new HouseholdService)->removeMember($household, $member, $owner))
            ->toThrow(AccessDeniedHttpException::class, 'Cannot remove the owner of the household.');
    });

});
