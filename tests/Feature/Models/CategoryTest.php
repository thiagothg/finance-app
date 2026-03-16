<?php

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\Household;
use App\Models\Transaction;

it('can create a category', function () {
    $category = Category::factory()->create();
    expect($category)->toBeInstanceOf(Category::class);
    expect($category->type)->toBeInstanceOf(CategoryType::class);
});

it('belongs to a household', function () {
    $category = Category::factory()->create();
    expect($category->household)->toBeInstanceOf(Household::class);
});

it('has transactions', function () {
    $category = Category::factory()->hasTransactions(2)->create();
    expect($category->transactions)->toHaveCount(2);
    expect($category->transactions->first())->toBeInstanceOf(Transaction::class);
});
