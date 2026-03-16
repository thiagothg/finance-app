<?php

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;

it('can create an account', function () {
    $account = Account::factory()->create();
    expect($account)->toBeInstanceOf(Account::class);
    expect($account->type)->toBeInstanceOf(AccountType::class);
});

it('belongs to a user', function () {
    $account = Account::factory()->create();
    expect($account->user)->toBeInstanceOf(User::class);
});

it('has transactions', function () {
    $account = Account::factory()->hasTransactions(3)->create();
    expect($account->transactions)->toHaveCount(3);
    expect($account->transactions->first())->toBeInstanceOf(Transaction::class);
});
