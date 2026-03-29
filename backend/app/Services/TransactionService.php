<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CurrencyEnum;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class TransactionService
{
    /**
     * List transactions for the user's household, filtered and grouped by date.
     *
     * @return array{groups: array<int, array<string, mixed>>, paginator: LengthAwarePaginator<int, Transaction>, accounts: Collection<int, Account>, categories: Collection<int, Category>, users: Collection<int, User>}
     */
    public function listTransactions(User $user, Request $request): array
    {
        $household = $user->household;

        $memberIds = $household
            ? $household->members()->pluck('user_id')->toArray()
            : [$user->id];

        $query = Transaction::query()
            ->with(['account', 'category', 'spender', 'toAccount'])
            ->whereIn('spender_user_id', $memberIds)
            ->latest('transaction_at')
            ->latest('id');

        $this->applyFilters($query, $request);

        $paginated = $query->paginate(15);

        /** @var Collection<int, Transaction> $items */
        $items = $paginated->getCollection();

        $grouped = $items->groupBy(function (Transaction $transaction): string {
            return $transaction->transaction_at->format('Y-m-d');
        });

        $formattedGroups = [];
        foreach ($grouped as $date => $group) {
            $sum = $group->sum(function (Transaction $t): float {
                if ($t->type === TransactionType::Expense) {
                    return -(float) $t->amount;
                } elseif ($t->type === TransactionType::Income) {
                    return (float) $t->amount;
                }

                return 0.0;
            });

            $formattedGroups[] = [
                'date' => $date,
                'sum' => round((float) $sum, 2),
                'transactions' => $group,
            ];
        }

        $accounts = Account::whereIn('user_id', $memberIds)->get();

        $categories = $household ? $household->categories()->get() : collect();

        $users = User::whereIn('id', $memberIds)->get();

        return [
            'groups' => $formattedGroups,
            'paginator' => $paginated,
            'accounts' => $accounts,
            'categories' => $categories,
            'users' => $users,
        ];
    }

    /**
     * Create a new transaction and update account balance.
     *
     * @param  array<string, mixed>  $data
     */
    public function createTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            $data['currency']      ??= CurrencyEnum::BRL->value;
            $data['exchange_rate'] ??= 1.0;
            $data['amount_base'] = round(
                (float) $data['amount'] * (float) $data['exchange_rate'],
                6,
            );

            /** @var Transaction $transaction */
            $transaction = Transaction::create($data);

            $this->updateAccountBalance($transaction, 1);

            $transaction->load(['account', 'category', 'spender', 'toAccount']);

            return $transaction;
        });
    }

    /**
     * Load relations for showing a transaction.
     */
    public function showTransaction(Transaction $transaction): Transaction
    {
        $transaction->load(['account', 'category', 'spender', 'toAccount']);

        return $transaction;
    }

    /**
     * Update an existing transaction and recalculate account balance.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateTransaction(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data) {
            // Revert original transaction balance effect
            $this->updateAccountBalance($transaction, -1);

            if (isset($data['amount']) || isset($data['exchange_rate'])) {
                $amount       = (float) ($data['amount']        ?? $transaction->amount);
                $exchangeRate = (float) ($data['exchange_rate'] ?? $transaction->exchange_rate ?? 1.0);

                $data['amount_base']   = round($amount * $exchangeRate, 6);
                $data['exchange_rate'] = $exchangeRate;
            }

            $transaction->update($data);

            // Apply new transaction balance effect
            $this->updateAccountBalance($transaction, 1);

            $transaction->load(['account', 'category', 'spender', 'toAccount']);

            return $transaction;
        });
    }

    /**
     * Delete a transaction and restore account balance.
     */
    public function deleteTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $this->updateAccountBalance($transaction, -1);
            $transaction->delete();
        });
    }

    /**
     * Update the balance of accounts involved in a transaction.
     *
     * Multiplier convention:
     *   +1 = applying the transaction (create)
     *   -1 = reverting the transaction (delete / pre-update)
     */
    private function updateAccountBalance(Transaction $transaction, int $multiplier = 1): void
    {
        $account = Account::findOrFail($transaction->account_id);
        $amount = $transaction->amount;
        $type = $transaction->type;

        if ($type === TransactionType::Expense) {
            $account->balance -= ($amount * $multiplier);
            $account->save();
        } elseif ($type === TransactionType::Income) {
            $account->balance += ($amount * $multiplier);
            $account->save();
        } elseif ($type === TransactionType::Transfer) {
            $account->balance -= ($amount * $multiplier);
            $account->save();

            if ($transaction->to_account_id) {
                $toAccount = Account::findOrFail($transaction->to_account_id);
                $toAccount->balance += ($amount * $multiplier);
                $toAccount->save();
            }
        }
    }

    /**
     * Apply request filters to the transaction query.
     *
     * @param  Builder<Transaction>  $query
     */
    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('household_id')) {
            $query->whereHas('spender.householdMember', function ($q) use ($request) {
                $q->where('household_id', $request->input('household_id'));
            });
        }

        if ($request->filled('account_id')) {
            $query->where('account_id', $request->input('account_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('transaction_at')) {
            $query->whereDate('transaction_at', $request->input('transaction_at'));
        }

        if ($request->filled('amount')) {
            $query->where('amount', $request->input('amount'));
        }

        if ($request->filled('description')) {
            $query->where('description', 'like', '%'.$request->input('description').'%');
        }

        if ($request->filled('spender_user_id')) {
            $query->where('spender_user_id', $request->input('spender_user_id'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->when(preg_match('/^\d{4}-\d{2}-\d{2}$/', $search), fn ($q) => $q->orWhereDate('transaction_at', $search))
                    ->orWhereHas('category', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('account', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('spender', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }
    }
}
