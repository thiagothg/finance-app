<?php

declare(strict_types=1);

use App\Enums\HouseholdMemberRole;
use App\Enums\HouseholdMemberStatus;
use App\Models\Account;
use App\Models\Category;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\HouseholdInvitationNotification;
use App\Services\HouseholdService;
use Illuminate\Support\Facades\Notification;
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
            ->and($household->members->first()->relationLoaded('user'))->toBeTrue()
            ->and($household->relationLoaded('currentUserMembership'))->toBeTrue()
            ->and($household->currentUserMembership?->status)->toBe(HouseholdMemberStatus::Accepted);
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
            'status' => HouseholdMemberStatus::Accepted->value,
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
            ->and($members->first()->relationLoaded('user'))->toBeTrue()
            ->and($members->first()->status)->toBeInstanceOf(HouseholdMemberStatus::class);
    });

});

describe('addMember', function (): void {

    it('allows an Owner to add a new member by email', function (): void {
        Notification::fake();

        $owner = User::factory()->create();
        $household = makeHousehold($owner);

        (new HouseholdService)->addMember($household, $owner, 'New Member', 'newmember@example.com', HouseholdMemberRole::Member);

        $newUser = User::where('email', 'newmember@example.com')->first();
        expect($newUser)->not->toBeNull();

        assertDatabaseHas('household_members', [
            'household_id' => $household->id,
            'user_id' => $newUser->id,
            'role' => HouseholdMemberRole::Member->value,
            'status' => HouseholdMemberStatus::Pending->value,
        ]);

        Notification::assertSentTo($newUser, HouseholdInvitationNotification::class);
    });

    it('allows a Member to add a new member by email', function (): void {
        Notification::fake();

        $owner = User::factory()->create();
        $member = User::factory()->create();
        $household = makeHousehold($owner, [['user' => $member, 'role' => HouseholdMemberRole::Member]]);

        (new HouseholdService)->addMember($household, $member, 'Another User', 'another@example.com', HouseholdMemberRole::Member);

        $newUser = User::where('email', 'another@example.com')->first();
        assertDatabaseHas('household_members', [
            'household_id' => $household->id,
            'user_id' => $newUser->id,
            'status' => HouseholdMemberStatus::Pending->value,
        ]);
    });

    it('adds an existing user by email without creating a new one', function (): void {
        Notification::fake();

        $owner = User::factory()->create();
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);
        $household = makeHousehold($owner);

        (new HouseholdService)->addMember($household, $owner, $existingUser->name, $existingUser->email, HouseholdMemberRole::Member);

        assertDatabaseHas('household_members', [
            'household_id' => $household->id,
            'user_id' => $existingUser->id,
            'status' => HouseholdMemberStatus::Accepted->value,
        ]);

        expect(User::where('email', 'existing@example.com')->count())->toBe(1);
    });

    it('throws AccessDeniedHttpException when a Viewer tries to add a member', function (): void {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $household = makeHousehold($owner, [['user' => $viewer, 'role' => HouseholdMemberRole::Viewer]]);

        expect(fn () => (new HouseholdService)->addMember($household, $viewer, 'New', 'new@example.com', HouseholdMemberRole::Member))
            ->toThrow(AccessDeniedHttpException::class, 'You do not have permission to add members.');
    });

    it('throws ConflictHttpException when the user to add already belongs to a household', function (): void {
        $owner = User::factory()->create();
        $otherOwner = User::factory()->create();
        $household = makeHousehold($owner);
        makeHousehold($otherOwner);

        expect(fn () => (new HouseholdService)->addMember($household, $owner, $otherOwner->name, $otherOwner->email, HouseholdMemberRole::Member))
            ->toThrow(ConflictHttpException::class, 'User is already part of a household.');
    });

    it('throws AccessDeniedHttpException when a non-member tries to add', function (): void {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $household = makeHousehold($owner);

        expect(fn () => (new HouseholdService)->addMember($household, $stranger, 'New', 'new@test.com', HouseholdMemberRole::Member))
            ->toThrow(AccessDeniedHttpException::class);
    });

});

describe('joinByCode', function (): void {

    it('creates an accepted membership when joining a household by code', function (): void {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $household = makeHousehold($owner);

        $joinedHousehold = (new HouseholdService)->joinByCode($user, $household->invitation_code);

        expect($joinedHousehold->id)->toBe($household->id);

        assertDatabaseHas('household_members', [
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => HouseholdMemberRole::Member->value,
            'status' => HouseholdMemberStatus::Accepted->value,
        ]);
    });

});

describe('acceptInvitation', function (): void {

    it('accepts a pending invitation for the authenticated user', function (): void {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $household = makeHousehold($owner);

        HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'user_id' => $invitedUser->id,
            'role' => HouseholdMemberRole::Member,
            'status' => HouseholdMemberStatus::Pending,
        ]);

        $acceptedHousehold = (new HouseholdService)->acceptInvitation($invitedUser, $household->invitation_code);

        expect($acceptedHousehold->id)->toBe($household->id);

        assertDatabaseHas('household_members', [
            'household_id' => $household->id,
            'user_id' => $invitedUser->id,
            'status' => HouseholdMemberStatus::Accepted->value,
        ]);
    });

});

describe('declineInvitation', function (): void {

    it('deletes a pending invitation for the authenticated user', function (): void {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $household = makeHousehold($owner);

        HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'user_id' => $invitedUser->id,
            'role' => HouseholdMemberRole::Member,
            'status' => HouseholdMemberStatus::Pending,
        ]);

        (new HouseholdService)->declineInvitation($invitedUser, $household->invitation_code);

        assertDatabaseMissing('household_members', [
            'household_id' => $household->id,
            'user_id' => $invitedUser->id,
        ]);
    });

});

describe('resendInvitation', function (): void {

    it('resends the invitation for a pending member', function (): void {
        Notification::fake();

        $owner = User::factory()->create();
        $pendingUser = User::factory()->create();
        $household = makeHousehold($owner);

        HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'user_id' => $pendingUser->id,
            'role' => HouseholdMemberRole::Member,
            'status' => HouseholdMemberStatus::Pending,
        ]);

        (new HouseholdService)->resendInvitation($household, $owner, $pendingUser);

        Notification::assertSentTo($pendingUser, HouseholdInvitationNotification::class);
    });

    it('throws when the invitation is not pending', function (): void {
        $owner = User::factory()->create();
        $acceptedUser = User::factory()->create();
        $household = makeHousehold($owner);

        HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'user_id' => $acceptedUser->id,
            'role' => HouseholdMemberRole::Member,
            'status' => HouseholdMemberStatus::Accepted,
        ]);

        expect(fn () => (new HouseholdService)->resendInvitation($household, $owner, $acceptedUser))
            ->toThrow(ConflictHttpException::class, 'Invitation can only be resent for pending members.');
    });

    it('throws when a viewer tries to resend an invitation', function (): void {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $pendingUser = User::factory()->create();
        $household = makeHousehold($owner, [['user' => $viewer, 'role' => HouseholdMemberRole::Viewer]]);

        HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'user_id' => $pendingUser->id,
            'role' => HouseholdMemberRole::Member,
            'status' => HouseholdMemberStatus::Pending,
        ]);

        expect(fn () => (new HouseholdService)->resendInvitation($household, $viewer, $pendingUser))
            ->toThrow(AccessDeniedHttpException::class, 'You do not have permission to resend invitations.');
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
