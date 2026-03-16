<?php

use App\Models\Category;
use App\Models\Household;
use App\Models\User;

it('can create a household', function () {
    $household = Household::factory()->create();
    expect($household)->toBeInstanceOf(Household::class);
});

it('belongs to an owner', function () {
    $household = Household::factory()->create();
    expect($household->owner)->toBeInstanceOf(User::class);
});

it('has categories', function () {
    $household = Household::factory()->hasCategories(5)->create();
    expect($household->categories)->toHaveCount(5);
    expect($household->categories->first())->toBeInstanceOf(Category::class);
});
