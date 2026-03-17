<?php

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

it('can create a transaction', function () {
    $transaction = Transaction::factory()->create();
    expect($transaction)->toBeInstanceOf(Transaction::class);
    expect($transaction->type)->toBeInstanceOf(TransactionType::class);
});

it('belongs to an account, category, and spender', function () {
    $transaction = Transaction::factory()->create();
    expect($transaction->account)->toBeInstanceOf(Account::class);
    expect($transaction->category)->toBeInstanceOf(Category::class);
    expect($transaction->spender)->toBeInstanceOf(User::class);
});

it('can be a transfer with a destination account', function () {
    $transaction = Transaction::factory()->create([
        'type' => TransactionType::Transfer,
        'to_account_id' => Account::factory(),
    ]);
    expect($transaction->toAccount)->toBeInstanceOf(Account::class);
});
