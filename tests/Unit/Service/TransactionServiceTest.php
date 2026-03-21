<?php

declare(strict_types=1);

use App\Enums\HouseholdMemberRole;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

use function Pest\Laravel\assertSoftDeleted;

uses(RefreshDatabase::class);

function householdWith(User $owner, array $members = []): Household
{
    $household = Household::factory()->create(['owner_id' => $owner->id]);

    HouseholdMember::factory()->create([
        'household_id' => $household->id,
        'user_id' => $owner->id,
        'role' => HouseholdMemberRole::Owner,
    ]);

    foreach ($members as $member) {
        HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'user_id' => $member->id,
            'role' => HouseholdMemberRole::Member,
        ]);
    }

    $owner->load('household');

    return $household;
}

function makeTransaction(User $user, Account $account, array $overrides = []): Transaction
{
    return Transaction::factory()->create(array_merge([
        'spender_user_id' => $user->id,
        'account_id' => $account->id,
        'type' => TransactionType::Expense,
        'amount' => 100.00,
    ], $overrides));
}

function makeRequest(array $params = []): Request
{
    return Request::create('/', 'GET', $params);
}

describe('listTransactions', function (): void {

    it('returns only own transactions when user has no household', function (): void {
        $user = User::factory()->create(); // no household
        $account = Account::factory()->create(['user_id' => $user->id]);
        $other = User::factory()->create();
        $otherAc = Account::factory()->create(['user_id' => $other->id]);

        makeTransaction($user, $account);
        makeTransaction($other, $otherAc);

        $result = (new TransactionService)->listTransactions($user, makeRequest());

        $allIds = collect($result['groups'])->flatMap(fn ($g) => $g['transactions'])->pluck('spender_user_id');
        expect($allIds->every(fn ($id) => $id === $user->id))->toBeTrue();
    });

    it('does not crash when user has no household (bug guard)', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 500]);
        makeTransaction($user, $account);

        // Should not throw — if it does, the null-household bug is present
        expect(fn () => (new TransactionService)->listTransactions($user, makeRequest()))
            ->not->toThrow(Error::class);
    });

    it('returns transactions from all household members', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        householdWith($owner, [$member]);

        $ownerAc = Account::factory()->create(['user_id' => $owner->id]);
        $memberAc = Account::factory()->create(['user_id' => $member->id]);

        makeTransaction($owner, $ownerAc);
        makeTransaction($member, $memberAc);

        $result = (new TransactionService)->listTransactions($owner, makeRequest());
        $total = collect($result['groups'])->sum(fn ($g) => count($g['transactions']));

        expect($total)->toEqual(2);
    });

    it('calculates a negative sum for expense transactions', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        householdWith($owner, [$member]);

        $account = Account::factory()->create(['user_id' => $owner->id]);
        makeTransaction($owner, $account, ['type' => TransactionType::Expense, 'amount' => 200.00, 'transaction_at' => '2025-06-01']);

        $result = (new TransactionService)->listTransactions($owner, makeRequest());

        $group = collect($result['groups'])->firstWhere('date', '2025-06-01');
        expect($group['sum'])->toEqual(-200.0);
    });

    it('calculates a positive sum for income transactions', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        householdWith($owner, [$member]);

        $account = Account::factory()->create(['user_id' => $owner->id]);
        makeTransaction($owner, $account, ['type' => TransactionType::Income, 'amount' => 500.00, 'transaction_at' => '2025-06-02']);

        $result = (new TransactionService)->listTransactions($owner, makeRequest());

        $group = collect($result['groups'])->firstWhere('date', '2025-06-02');
        expect($group['sum'])->toEqual(500.0);
    });

    it('calculates zero sum for transfer transactions', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        householdWith($owner, [$member]);

        $accountA = Account::factory()->create(['user_id' => $owner->id]);
        $accountB = Account::factory()->create(['user_id' => $owner->id]);

        makeTransaction($owner, $accountA, [
            'type' => TransactionType::Transfer,
            'amount' => 300.00,
            'to_account_id' => $accountB->id,
            'transaction_at' => '2025-06-03',
        ]);

        $result = (new TransactionService)->listTransactions($owner, makeRequest());

        $group = collect($result['groups'])->firstWhere('date', '2025-06-03');
        expect($group['sum'])->toEqual(0.0);
    });

    it('groups transactions by date correctly', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        householdWith($owner, [$member]);

        $account = Account::factory()->create(['user_id' => $owner->id]);

        makeTransaction($owner, $account, ['transaction_at' => '2025-06-01']);
        makeTransaction($owner, $account, ['transaction_at' => '2025-06-01']);
        makeTransaction($owner, $account, ['transaction_at' => '2025-06-02']);

        $result = (new TransactionService)->listTransactions($owner, makeRequest());

        expect($result['groups'])->toHaveCount(2);

        $june1 = collect($result['groups'])->firstWhere('date', '2025-06-01');
        expect($june1['transactions'])->toHaveCount(2);
    });

    it('returns paginator, accounts, categories, and users in the result', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        householdWith($owner, [$member]);

        $result = (new TransactionService)->listTransactions($owner, makeRequest());

        expect($result)->toHaveKeys(['groups', 'paginator', 'accounts', 'categories', 'users']);
    });

    // -----------------------------------------------------------------------
    // applyFilters
    // -----------------------------------------------------------------------

    it('filters by household_id', function (): void {
        $owner1 = User::factory()->create();
        $household1 = householdWith($owner1);

        $owner2 = User::factory()->create();
        $household2 = householdWith($owner2);

        $account1 = Account::factory()->create(['user_id' => $owner1->id]);
        $account2 = Account::factory()->create(['user_id' => $owner2->id]);

        makeTransaction($owner1, $account1);
        makeTransaction($owner2, $account2);

        $result = (new TransactionService)->listTransactions($owner1, makeRequest(['household_id' => $household1->id]));
        $ids = collect($result['groups'])->flatMap(fn ($g) => $g['transactions'])->pluck('account_id');

        expect($ids->every(fn ($id) => $id === $account1->id))->toBeTrue()
            ->and($ids)->toHaveCount(1);
    });

    it('filters by account_id', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        householdWith($owner, [$member]);

        $accountA = Account::factory()->create(['user_id' => $owner->id]);
        $accountB = Account::factory()->create(['user_id' => $owner->id]);

        makeTransaction($owner, $accountA);
        makeTransaction($owner, $accountB);

        $result = (new TransactionService)->listTransactions($owner, makeRequest(['account_id' => $accountA->id]));
        $ids = collect($result['groups'])->flatMap(fn ($g) => $g['transactions'])->pluck('account_id');

        expect($ids->every(fn ($id) => $id === $accountA->id))->toBeTrue();
    });

    it('filters by category_id', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        householdWith($owner, [$member]);

        $account = Account::factory()->create(['user_id' => $owner->id]);
        $catA = Category::factory()->create();
        $catB = Category::factory()->create();

        makeTransaction($owner, $account, ['category_id' => $catA->id]);
        makeTransaction($owner, $account, ['category_id' => $catB->id]);

        $result = (new TransactionService)->listTransactions($owner, makeRequest(['category_id' => $catA->id]));
        $cats = collect($result['groups'])->flatMap(fn ($g) => $g['transactions'])->pluck('category_id');

        expect($cats->every(fn ($id) => $id === $catA->id))->toBeTrue();
    });

    it('filters by type', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        householdWith($owner, [$member]);

        $account = Account::factory()->create(['user_id' => $owner->id]);

        makeTransaction($owner, $account, ['type' => TransactionType::Expense]);
        makeTransaction($owner, $account, ['type' => TransactionType::Income]);

        $result = (new TransactionService)->listTransactions($owner, makeRequest(['type' => TransactionType::Income->value]));
        $types = collect($result['groups'])->flatMap(fn ($g) => $g['transactions'])->pluck('type');

        expect($types->every(fn ($t) => $t === TransactionType::Income))->toBeTrue();
    });

    it('filters by transaction_at date', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        householdWith($owner, [$member]);

        $account = Account::factory()->create(['user_id' => $owner->id]);

        makeTransaction($owner, $account, ['transaction_at' => '2025-05-01']);
        makeTransaction($owner, $account, ['transaction_at' => '2025-06-15']);

        $result = (new TransactionService)->listTransactions($owner, makeRequest(['transaction_at' => '2025-06-15']));
        $total = collect($result['groups'])->sum(fn ($g) => count($g['transactions']));

        expect($total)->toEqual(1);
    });

    it('filters by amount', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        householdWith($owner, [$member]);

        $account = Account::factory()->create(['user_id' => $owner->id]);

        makeTransaction($owner, $account, ['amount' => 99.99]);
        makeTransaction($owner, $account, ['amount' => 250.00]);

        $result = (new TransactionService)->listTransactions($owner, makeRequest(['amount' => 250.00]));
        $amounts = collect($result['groups'])->flatMap(fn ($g) => $g['transactions'])->pluck('amount');

        expect($amounts->every(fn ($a) => (float) $a === 250.00))->toBeTrue();
    });

    it('filters by description (partial match)', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        householdWith($owner, [$member]);

        $account = Account::factory()->create(['user_id' => $owner->id]);

        makeTransaction($owner, $account, ['description' => 'Grocery shopping']);
        makeTransaction($owner, $account, ['description' => 'Netflix subscription']);

        $result = (new TransactionService)->listTransactions($owner, makeRequest(['description' => 'Grocery']));
        $descs = collect($result['groups'])->flatMap(fn ($g) => $g['transactions'])->pluck('description');

        expect($descs->every(fn ($d) => str_contains($d, 'Grocery')))->toBeTrue();
    });

    it('filters by spender_user_id', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        householdWith($owner, [$member]);

        $ownerAc = Account::factory()->create(['user_id' => $owner->id]);
        $memberAc = Account::factory()->create(['user_id' => $member->id]);

        makeTransaction($owner, $ownerAc);
        makeTransaction($member, $memberAc);

        $result = (new TransactionService)->listTransactions($owner, makeRequest(['spender_user_id' => $member->id]));
        $spenders = collect($result['groups'])->flatMap(fn ($g) => $g['transactions'])->pluck('spender_user_id');

        expect($spenders->every(fn ($id) => $id === $member->id))->toBeTrue();
    });

    it('applies global search across description, amount, type, and relations', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        householdWith($owner, [$member]);

        $account = Account::factory()->create(['user_id' => $owner->id, 'name' => 'Main Wallet']);

        makeTransaction($owner, $account, ['description' => 'unique-keyword-xyz']);
        makeTransaction($owner, $account, ['description' => 'should not appear']);

        $result = (new TransactionService)->listTransactions($owner, makeRequest(['search' => 'unique-keyword-xyz']));
        $total = collect($result['groups'])->sum(fn ($g) => count($g['transactions']));

        expect($total)->toEqual(1);
    });

    it('global search matches by account name', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        householdWith($owner, [$member]);

        $account = Account::factory()->create(['user_id' => $owner->id, 'name' => 'SpecialWalletABC']);

        makeTransaction($owner, $account, ['description' => 'generic']);
        makeTransaction($owner, Account::factory()->create(['user_id' => $owner->id, 'name' => 'Other']), ['description' => 'generic']);

        $result = (new TransactionService)->listTransactions($owner, makeRequest(['search' => 'SpecialWalletABC']));
        $total = collect($result['groups'])->sum(fn ($g) => count($g['transactions']));

        expect($total)->toEqual(1);
    });

});

describe('createTransaction', function (): void {

    it('persists the transaction and decreases balance for expense', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 1000.00]);

        (new TransactionService)->createTransaction([
            'spender_user_id' => $user->id,
            'account_id' => $account->id,
            'type' => TransactionType::Expense,
            'amount' => 200.00,
            'description' => 'Lunch',
            'transaction_at' => now(),
        ]);

        expect($account->fresh()->balance)->toEqual(800.00);
    });

    it('increases balance for income transaction', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 500.00]);

        (new TransactionService)->createTransaction([
            'spender_user_id' => $user->id,
            'account_id' => $account->id,
            'type' => TransactionType::Income,
            'amount' => 300.00,
            'description' => 'Salary',
            'transaction_at' => now(),
        ]);

        expect($account->fresh()->balance)->toEqual(800.00);
    });

    it('debits source and credits destination for transfer transaction', function (): void {
        $user = User::factory()->create();
        $accountA = Account::factory()->create(['user_id' => $user->id, 'balance' => 1000.00]);
        $accountB = Account::factory()->create(['user_id' => $user->id, 'balance' => 200.00]);

        (new TransactionService)->createTransaction([
            'spender_user_id' => $user->id,
            'account_id' => $accountA->id,
            'to_account_id' => $accountB->id,
            'type' => TransactionType::Transfer,
            'amount' => 400.00,
            'description' => 'Transfer',
            'transaction_at' => now(),
        ]);

        expect($accountA->fresh()->balance)->toEqual(600.00)
            ->and($accountB->fresh()->balance)->toEqual(600.00);
    });

    it('handles transfer with no to_account_id — only debits source', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 1000.00]);

        (new TransactionService)->createTransaction([
            'spender_user_id' => $user->id,
            'account_id' => $account->id,
            'to_account_id' => null,
            'type' => TransactionType::Transfer,
            'amount' => 100.00,
            'description' => 'External transfer',
            'transaction_at' => now(),
        ]);

        expect($account->fresh()->balance)->toEqual(900.00);
    });

    it('loads relations on the returned transaction', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 500.00]);

        $transaction = (new TransactionService)->createTransaction([
            'spender_user_id' => $user->id,
            'account_id' => $account->id,
            'type' => TransactionType::Expense,
            'amount' => 50.00,
            'description' => 'Coffee',
            'transaction_at' => now(),
        ]);

        expect($transaction->relationLoaded('account'))->toBeTrue()
            ->and($transaction->relationLoaded('spender'))->toBeTrue();
    });

    it('rolls back on failure — balance is not changed if transaction save fails', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 1000.00]);

        // Force an exception inside the DB::transaction by passing an invalid account_id
        expect(fn () => (new TransactionService)->createTransaction([
            'spender_user_id' => $user->id,
            'account_id' => 99999, // non-existent — findOrFail will throw
            'type' => TransactionType::Expense,
            'amount' => 500.00,
            'description' => 'Should fail',
            'transaction_at' => now(),
        ]))->toThrow(Exception::class);

        // Balance must be unchanged
        expect($account->fresh()->balance)->toEqual(1000.00);
    });

});

describe('updateTransaction', function (): void {

    it('reverts original balance and applies updated amount for expense', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 800.00]);

        $transaction = makeTransaction($user, $account, [
            'type' => TransactionType::Expense,
            'amount' => 200.00,
        ]);
        // Simulate that balance was already affected: 1000 - 200 = 800
        $account->balance = 800.00;
        $account->save();

        (new TransactionService)->updateTransaction($transaction, [
            'amount' => 100.00,
            'type' => TransactionType::Expense,
        ]);

        // Revert 200 → 1000, apply 100 → 900
        expect($account->fresh()->balance)->toEqual(900.00);
    });

    it('reverts and reapplies balance when switching from expense to income', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 800.00]);

        $transaction = makeTransaction($user, $account, [
            'type' => TransactionType::Expense,
            'amount' => 200.00,
        ]);

        (new TransactionService)->updateTransaction($transaction, [
            'type' => TransactionType::Income->value,
            'amount' => 200.00,
        ]);

        // Revert expense (-200 revert = +200 → 1000), apply income (+200 → 1200)
        expect($account->fresh()->balance)->toEqual(1200.00);
    });

    it('loads relations on the returned updated transaction', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 500.00]);

        $transaction = makeTransaction($user, $account, ['type' => TransactionType::Expense, 'amount' => 50.00]);

        $updated = (new TransactionService)->updateTransaction($transaction, ['description' => 'Updated desc']);

        expect($updated->relationLoaded('account'))->toBeTrue();
    });

    it('only adjusts the source account when updating a transfer with no to_account_id', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 600.00]);

        $transaction = Transaction::factory()->create([
            'spender_user_id' => $user->id,
            'account_id' => $account->id,
            'to_account_id' => null,
            'type' => TransactionType::Transfer,
            'amount' => 100.00,
            'transaction_at' => now(),
        ]);

        // Pre-state: balance already reflects the original transfer (600 - 100 = 500)
        $account->balance = 500.00;
        $account->save();

        // Update the amount — revert 100, apply 200
        (new TransactionService)->updateTransaction($transaction, ['amount' => 200.00]);

        // Revert: 500 + 100 = 600, Apply: 600 - 200 = 400
        expect($account->fresh()->balance)->toEqual(400.00);
    });

    it('reverts source-only transfer during deleteTransaction when to_account_id is null', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 700.00]);

        $transaction = Transaction::factory()->create([
            'spender_user_id' => $user->id,
            'account_id' => $account->id,
            'to_account_id' => null,
            'type' => TransactionType::Transfer,
            'amount' => 200.00,
            'transaction_at' => now(),
        ]);

        // Pre-state: balance already affected (900 - 200 = 700)
        (new TransactionService)->deleteTransaction($transaction);

        // Revert: 700 + 200 = 900
        expect($account->fresh()->balance)->toEqual(900.00);
        assertSoftDeleted('transactions', ['id' => $transaction->id]);
    });

});

describe('deleteTransaction', function (): void {

    it('soft-deletes the transaction and reverts balance for expense', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 800.00]);

        $transaction = makeTransaction($user, $account, [
            'type' => TransactionType::Expense,
            'amount' => 200.00,
        ]);

        (new TransactionService)->deleteTransaction($transaction);

        // Balance reverted: 800 + 200 = 1000
        expect($account->fresh()->balance)->toEqual(1000.00);
        assertSoftDeleted('transactions', ['id' => $transaction->id]);
    });

    it('soft-deletes the transaction and reverts balance for income', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 1500.00]);

        $transaction = makeTransaction($user, $account, [
            'type' => TransactionType::Income,
            'amount' => 500.00,
        ]);

        (new TransactionService)->deleteTransaction($transaction);

        // Balance reverted: 1500 - 500 = 1000
        expect($account->fresh()->balance)->toEqual(1000.00);
    });

    it('reverts both source and destination balances when deleting a transfer', function (): void {
        $user = User::factory()->create();
        $accountA = Account::factory()->create(['user_id' => $user->id, 'balance' => 600.00]);
        $accountB = Account::factory()->create(['user_id' => $user->id, 'balance' => 600.00]);

        $transaction = makeTransaction($user, $accountA, [
            'type' => TransactionType::Transfer,
            'amount' => 400.00,
            'to_account_id' => $accountB->id,
        ]);

        (new TransactionService)->deleteTransaction($transaction);

        // Revert: A 600 + 400 = 1000, B 600 - 400 = 200
        expect($accountA->fresh()->balance)->toEqual(1000.00)
            ->and($accountB->fresh()->balance)->toEqual(200.00);
    });

});

describe('showTransaction', function (): void {

    it('loads all required relations', function (): void {
        $user = User::factory()->create();
        $account = Account::factory()->create(['user_id' => $user->id, 'balance' => 500.00]);

        $transaction = makeTransaction($user, $account);
        $result = (new TransactionService)->showTransaction($transaction);

        expect($result->relationLoaded('account'))->toBeTrue()
            ->and($result->relationLoaded('spender'))->toBeTrue()
            ->and($result->relationLoaded('category'))->toBeTrue()
            ->and($result->relationLoaded('toAccount'))->toBeTrue();
    });

});
