<?php

namespace App\Policies;

use App\Enums\HouseholdMemberRole;
use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    /**
     * Identify if user is in the same household as the target account user owner.
     */
    private function isSameHousehold(User $user, int $targetUserId): bool
    {
        if ($user->id === $targetUserId) {
            return true;
        }

        if (! $user->household) {
            return false;
        }

        $memberIds = $user->household->members()->pluck('user_id')->toArray();

        return in_array($targetUserId, $memberIds);
    }

    /**
     * Identify if user is the Owner of the household containing the target account user owner.
     */
    private function isHouseholdOwner(User $user, int $targetUserId): bool
    {
        if (! $user->household) {
            return false;
        }

        $memberRoles = $user->household->members()
            ->whereIn('user_id', [$user->id, $targetUserId])
            ->get()
            ->keyBy('user_id');

        if (! isset($memberRoles[$targetUserId]) || ! isset($memberRoles[$user->id])) {
            return false; // Not in the same household or invalid data
        }

        return $memberRoles[$user->id]->role === HouseholdMemberRole::Owner;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Account $account): bool
    {
        return $this->isSameHousehold($user, $account->user_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Account $account): bool
    {
        if ($user->id === $account->user_id) {
            return true;
        }

        return $this->isHouseholdOwner($user, $account->user_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Account $account): bool
    {
        if ($user->id === $account->user_id) {
            return true;
        }

        return $this->isHouseholdOwner($user, $account->user_id);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Account $account): bool
    {
        return $this->delete($user, $account);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Account $account): bool
    {
        return $this->delete($user, $account);
    }
}
