<?php

declare(strict_types=1);

use App\Enums\CurrencyEnum;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Household;
use App\Models\User;
use App\Services\TransactionService;

function setupTransactionTestDependencies(): array
{
    $transactionService = app(TransactionService::class);
    $user = User::factory()->create();
    $household = Household::factory()->create(['owner_id' => $user->id]);
    $account = Account::factory()->create(['user_id' => $user->id]);
    $category = Category::factory()->create(['household_id' => $household->id]);

    return [$transactionService, $user, $account, $category];
}

it('creates a transaction with custom currency and exchange rate', function () {
    [$transactionService, $user, $account, $category] = setupTransactionTestDependencies();

    $data = [
        'account_id' => $account->id,
        'category_id' => $category->id,
        'spender_user_id' => $user->id,
        'amount' => 100.0,
        'type' => TransactionType::Expense->value,
        'transaction_at' => now()->toDateString(),
        'description' => 'Test Transaction',
        'currency' => CurrencyEnum::USD->value,
        'exchange_rate' => 5.5,
    ];

    $transaction = $transactionService->createTransaction($data);

    expect($transaction->currency)->toBe(CurrencyEnum::USD->value)
        ->and((float) $transaction->exchange_rate)->toBe(5.5)
        ->and((float) $transaction->amount_base)->toBe(550.0) // 100 * 5.5
        ->and((float) $transaction->amount)->toBe(100.0);
});

it('recalculates amount base when updating transaction amount or rate', function () {
    [$transactionService, $user, $account, $category] = setupTransactionTestDependencies();

    $data = [
        'account_id' => $account->id,
        'category_id' => $category->id,
        'spender_user_id' => $user->id,
        'amount' => 100.0,
        'type' => TransactionType::Expense->value,
        'transaction_at' => now()->toDateString(),
        'description' => 'Test',
        'currency' => CurrencyEnum::USD->value,
        'exchange_rate' => 5.0, // amount_base = 500
    ];

    $transaction = $transactionService->createTransaction($data);

    expect((float) $transaction->amount_base)->toBe(500.0);

    // Update the amount, base should automatically multiply stringently.
    $updated = $transactionService->updateTransaction($transaction, [
        'amount' => 200.0,
    ]);

    expect((float) $updated->amount_base)->toBe(1000.0);

    // Update the exchange rate, amount base should multiply stringently.
    $updatedAgain = $transactionService->updateTransaction($updated, [
        'exchange_rate' => 6.0,
    ]);

    expect((float) $updatedAgain->amount_base)->toBe(1200.0);
});
