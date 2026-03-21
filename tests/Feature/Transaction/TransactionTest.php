<?php

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\getJson;

it('requires authentication for transactions endpoints', function () {
    $response = getJson('/api/v1/transactions');
    $response->assertUnauthorized();
});

it('lists transactions with grouping and meta data', function () {
    /** @var Authenticatable $user */
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    $category = Category::factory()->create(['user_id' => $user->id]);

    $household = \App\Models\Household::factory()->create(['owner_id' => $user->id]);
    \App\Models\HouseholdMember::factory()->create([
        'user_id' => $user->id,
        'household_id' => $household->id,
    ]);

    Transaction::factory()->create([
        'spender_user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'transaction_at' => now()->subDay(),
        'amount' => 100,
        'type' => TransactionType::Expense->value,
    ]);

    actingAs($user)
        ->getJson('/api/v1/transactions')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'date',
                    'sum',
                    'transactions',
                ],
            ],
            'meta' => [
                'current_page',
                'accounts',
                'categories',
                'users',
            ],
        ]);
});

it('can create an expense transaction and update balance', function () {
    /** @var Authenticatable $user */
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 500]);
    $category = Category::factory()->create(['user_id' => $user->id]);

    $payload = [
        'amount' => 100,
        'type' => TransactionType::Expense->value,
        'category_id' => $category->id,
        'account_id' => $account->id,
        'spender_user_id' => $user->id,
        'transaction_at' => now()->format('Y-m-d H:i:s'),
        'description' => 'Groceries',
    ];

    actingAs($user)
        ->postJson('/api/v1/transactions', $payload)
        ->assertCreated();

    assertDatabaseHas('transactions', [
        'amount' => 100,
        'description' => 'Groceries',
    ]);

    expect((string) $account->fresh()->balance)->toBe('400.00'); // 500 - 100
});

it('can create an income transaction and update balance', function () {
    /** @var Authenticatable $user */
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 500]);
    $category = Category::factory()->create(['user_id' => $user->id]);

    $payload = [
        'amount' => 200,
        'type' => TransactionType::Income->value,
        'category_id' => $category->id,
        'account_id' => $account->id,
        'spender_user_id' => $user->id,
        'transaction_at' => now()->toDateTimeString(),
    ];

    actingAs($user)
        ->postJson('/api/v1/transactions', $payload)
        ->assertCreated();

    expect((string) $account->fresh()->balance)->toBe('700.00'); // 500 + 200
});

it('can create a transfer transaction and update both balances', function () {
    /** @var Authenticatable $user */
    $user = User::factory()->create();
    $accountFrom = Account::factory()->create(['user_id' => $user->id, 'balance' => 500]);
    $accountTo = Account::factory()->create(['user_id' => $user->id, 'balance' => 100]);
    $category = Category::factory()->create(['user_id' => $user->id]);

    $payload = [
        'amount' => 150,
        'type' => TransactionType::Transfer->value,
        'category_id' => $category->id,
        'account_id' => $accountFrom->id,
        'to_account_id' => $accountTo->id,
        'spender_user_id' => $user->id,
        'transaction_at' => now()->toDateTimeString(),
    ];

    actingAs($user)
        ->postJson('/api/v1/transactions', $payload)
        ->assertCreated();

    expect((string) $accountFrom->fresh()->balance)->toBe('350.00'); // 500 - 150
    expect((string) $accountTo->fresh()->balance)->toBe('250.00'); // 100 + 150
});

it('can update a transaction and recalculate balance', function () {
    /** @var Authenticatable $user */
    $user = User::factory()->create();
    // Start with 500 balance
    $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 400]); // Balance after initial 100 expense
    $category = Category::factory()->create(['user_id' => $user->id]);

    $transaction = Transaction::factory()->create([
        'spender_user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'amount' => 100,
        'type' => TransactionType::Expense->value,
    ]);

    $payload = [
        'amount' => 150, // Increase expense to 150
        'type' => TransactionType::Expense->value,
        'category_id' => $category->id,
        'account_id' => $account->id,
        'spender_user_id' => $user->id,
        'transaction_at' => now()->toDateTimeString(),
    ];

    actingAs($user)
        ->putJson("/api/v1/transactions/{$transaction->id}", $payload)
        ->assertOk();

    // Revert -100 (balance becomes 500), Apply -150 (balance becomes 350)
    expect((string) $account->fresh()->balance)->toBe('350.00');
});

it('can delete a transaction and restore balance', function () {
    /** @var Authenticatable $user */
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 400]); // Assuming 500 - 100 = 400
    $category = Category::factory()->create(['user_id' => $user->id]);

    $transaction = Transaction::factory()->create([
        'spender_user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'amount' => 100,
        'type' => TransactionType::Expense->value,
    ]);

    actingAs($user)
        ->deleteJson("/api/v1/transactions/{$transaction->id}")
        ->assertNoContent();

    assertSoftDeleted('transactions', ['id' => $transaction->id]);
    expect((string) $account->fresh()->balance)->toBe('500.00'); // Reverted the 100 expense
});
