<?php

use App\Models\User;

it('can create a user', function () {
    $user = User::factory()->create();
    expect($user)->toBeInstanceOf(User::class);
});

it('has accounts', function () {
    $user = User::factory()->hasAccounts(3)->create();
    expect($user->accounts)->toHaveCount(3);
});

it('has households', function () {
    $user = User::factory()->hasHouseholds(2, ['owner_id' => null])->create();
    expect($user->households)->toHaveCount(2);
});
