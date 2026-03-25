<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Household;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final readonly class AccountService
{
    /**
     * List all accounts visible to the user (own + household members).
     *
     * @return array{accounts: Collection<int, Account>, total_balance: float}
     */
    public function listAccounts(User $user): array
    {
        $household = $user->household()->first();

        $memberIds = [];
        if ($household instanceof Household) {
            $memberIds = $household->members()->pluck('user_id')->toArray();
        } else {
            $memberIds = [$user->id];
        }

        /** @var Collection<int, Account> $accounts */
        $accounts = Account::query()->with('user')
            ->whereIn('user_id', $memberIds)
            ->get();

        $totalSum = (float) $accounts->sum(function (Account $account) {
            // If the account has a balance in a non-BRL currency, we need amount_base.
            // For now, use balance directly — update when balance column stores BRL equivalent.
            return (float) ($account->balance != 0 ? $account->balance : $account->initial_balance);
        });

        return [
            'accounts' => $accounts,
            'total_balance' => round($totalSum, 2),
        ];
    }

    /**
     * Create a new account.
     *
     * @param  array<string, mixed>  $data
     */
    public function createAccount(User $user, array $data): Account
    {
        if (! isset($data['user_id'])) {
            $data['user_id'] = $user->id;
        }

        /** @var Account $account */
        $account = Account::query()->create($data);

        $account->load('user');

        return $account;
    }

    /**
     * Update an existing account.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateAccount(Account $account, array $data): Account
    {
        $account->update($data);

        $account->load('user');

        return $account;
    }

    /**
     * Delete an account (soft-delete).
     */
    public function deleteAccount(Account $account): void
    {
        $account->delete();
    }
}
