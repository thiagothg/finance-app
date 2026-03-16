<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\HouseholdMemberRole;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class HouseholdService
{
    /**
     * Get the user's household, wrapping it in a collection for the response format.
     *
     * @return Collection<int, Household>
     */
    public function getByUser(User $user): Collection
    {
        /** @var Household|null $household */
        $household = $user->household()
            ->withCount('members')
            ->with(['members.user'])
            ->first();

        if (! $household) {
            return new Collection;
        }

        // Calculate total spend per member.
        // We need transactions from categories belonging to this household.
        $members = $household->members;

        $spends = DB::table('transactions')
            ->join('categories', 'transactions.category_id', '=', 'categories.id')
            ->where('categories.household_id', $household->id)
            ->whereIn('transactions.spender_user_id', $members->pluck('user_id'))
            ->select('transactions.spender_user_id', DB::raw('SUM(transactions.amount) as total_spend'))
            ->groupBy('transactions.spender_user_id')
            ->get()
            ->keyBy('spender_user_id');

        /** @var HouseholdMember $member */
        foreach ($members as $member) {
            $spendRecord = $spends->get($member->user_id);
            $total = $spendRecord ? (float) $spendRecord->total_spend : 0.0;

            $member->setAttribute('total_spend', $total);
        }

        return new Collection([$household]);
    }

    /**
     * Create or update a household name
     */
    public function upsertName(User $user, string $name, ?Household $household = null): Household
    {
        if ($household === null) {
            // Creating a new household. User must not be in one.
            if ($user->householdMember()->exists()) {
                throw new ConflictHttpException('User is already part of a household.');
            }

            return DB::transaction(function () use ($user, $name) {
                $household = Household::create([
                    'owner_id' => $user->id,
                    'name' => $name,
                ]);

                $household->members()->create([
                    'user_id' => $user->id,
                    'role' => HouseholdMemberRole::Owner,
                ]);

                return $household;
            });
        }

        // Updating existing. Check permissions.
        /** @var HouseholdMember|null $member */
        $member = $household->members()->where('user_id', $user->id)->first();
        if (! $member || $member->role === HouseholdMemberRole::Viewer) {
            throw new AccessDeniedHttpException('You do not have permission to edit this household.');
        }

        $household->update(['name' => $name]);

        return $household;
    }

    /**
     * List members of a household
     *
     * @return Collection<int, HouseholdMember>
     */
    public function listMembers(Household $household): Collection
    {
        /** @var Collection<int, HouseholdMember> $members */
        $members = $household->members()->with('user')->get();

        return $members;
    }

    /**
     * Add a member to the household
     */
    public function addMember(Household $household, User $actor, User $userToAdd, HouseholdMemberRole $role): void
    {
        /** @var HouseholdMember|null $actorMember */
        $actorMember = $household->members()->where('user_id', $actor->id)->first();

        if (! $actorMember || $actorMember->role === HouseholdMemberRole::Viewer) {
            throw new AccessDeniedHttpException('You do not have permission to add members.');
        }

        if ($userToAdd->householdMember()->exists()) {
            throw new ConflictHttpException('User is already part of a household.');
        }

        $household->members()->create([
            'user_id' => $userToAdd->id,
            'role' => $role,
        ]);
    }

    /**
     * Remove a member from the household
     */
    public function removeMember(Household $household, User $actor, User $userToRemove): void
    {
        /** @var HouseholdMember|null $actorMember */
        $actorMember = $household->members()->where('user_id', $actor->id)->first();

        // Users can remove themselves, but to remove others, must be Owner or Member (not Viewer).
        // Owners can't be removed, unless they are removing themselves ?
        // Requirements say "Add/Remove members of a household", usually Owner can remove anyone, Member probably shouldn't remove Owner.
        // I will allow Owner and Member to remove anyone, except Owner removing Owner.

        $isSelfRemoval = $actor->id === $userToRemove->id;

        if (! $isSelfRemoval) {
            if (! $actorMember || $actorMember->role === HouseholdMemberRole::Viewer) {
                throw new AccessDeniedHttpException('You do not have permission to remove members.');
            }
        }

        /** @var HouseholdMember|null $memberToRemove */
        $memberToRemove = $household->members()->where('user_id', $userToRemove->id)->first();

        if (! $memberToRemove) {
            throw new NotFoundHttpException('User is not a member of this household.');
        }

        if ($memberToRemove->role === HouseholdMemberRole::Owner && ! $isSelfRemoval) {
            throw new AccessDeniedHttpException('Cannot remove the owner of the household.');
        }

        $memberToRemove->delete();
    }
}
