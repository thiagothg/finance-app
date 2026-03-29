<?php

declare(strict_types=1);

use App\Enums\CategoryType;
use App\Enums\HouseholdMemberRole;
use App\Models\Account;
use App\Models\Category;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CategoryService;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function Pest\Laravel\assertSoftDeleted;

function householdWithRoles(array $roleMap): Household
{
    $ownerEntry = collect($roleMap)->firstWhere('role', HouseholdMemberRole::Owner);
    $owner = $ownerEntry['user'];

    $household = Household::factory()->create(['owner_id' => $owner->id]);

    foreach ($roleMap as ['user' => $user, 'role' => $role]) {
        HouseholdMember::factory()->create([
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => $role,
        ]);
    }

    $owner->load('household');

    return $household;
}

function categoryFor(Household $household, User $user, array $overrides = []): Category
{
    return Category::factory()->create(array_merge([
        'household_id' => $household->id,
        'user_id' => $user->id,
        'type' => CategoryType::Expense,
    ], $overrides));
}

describe('listCategories', function (): void {

    // Line 31 — user has no household
    it('returns empty collection when user has no household', function (): void {
        $user = User::factory()->create();
        $user->setRelation('household', null);

        $result = (new CategoryService)->listCategories($user);

        expect($result)->toBeEmpty();
    });

    // Line 45 — household exists but has no categories
    it('returns empty collection when household has no categories', function (): void {
        $owner = User::factory()->create();
        householdWithRoles([['user' => $owner, 'role' => HouseholdMemberRole::Owner]]);
        $owner->load('household');

        $result = (new CategoryService)->listCategories($owner);

        expect($result)->toBeEmpty();
    });

    it('returns categories grouped by type', function (): void {
        $owner = User::factory()->create();
        $household = householdWithRoles([['user' => $owner, 'role' => HouseholdMemberRole::Owner]]);
        $owner->load('household');

        categoryFor($household, $owner, ['type' => CategoryType::Expense]);
        categoryFor($household, $owner, ['type' => CategoryType::Income]);

        $result = (new CategoryService)->listCategories($owner);

        expect($result->keys()->sort()->values()->toArray())
            ->toEqual(collect([CategoryType::Expense->value, CategoryType::Income->value])->sort()->values()->toArray());
    });

    it('filters by type when provided', function (): void {
        $owner = User::factory()->create();
        $household = householdWithRoles([['user' => $owner, 'role' => HouseholdMemberRole::Owner]]);
        $owner->load('household');

        categoryFor($household, $owner, ['type' => CategoryType::Expense]);
        categoryFor($household, $owner, ['type' => CategoryType::Income]);

        $result = (new CategoryService)->listCategories($owner, CategoryType::Expense->value);

        expect($result->keys()->toArray())->toEqual([CategoryType::Expense->value]);
    });

    it('calculates total_spend per category from transactions', function (): void {
        $owner = User::factory()->create();
        $household = householdWithRoles([['user' => $owner, 'role' => HouseholdMemberRole::Owner]]);
        $owner->load('household');

        $account = Account::factory()->create(['user_id' => $owner->id, 'balance' => 1000]);
        $category = categoryFor($household, $owner);

        Transaction::factory()->create([
            'category_id' => $category->id,
            'account_id' => $account->id,
            'spender_user_id' => $owner->id,
            'amount' => 150.00,
        ]);

        $result = (new CategoryService)->listCategories($owner);
        $categories = $result->first();
        $found = $categories->firstWhere('id', $category->id);

        expect($found->total_spend)->toBe(150.0);
    });

    it('sets total_spend to 0.0 for categories without transactions', function (): void {
        $owner = User::factory()->create();
        $household = householdWithRoles([['user' => $owner, 'role' => HouseholdMemberRole::Owner]]);
        $owner->load('household');

        $category = categoryFor($household, $owner);

        $result = (new CategoryService)->listCategories($owner);
        $found = $result->first()->firstWhere('id', $category->id);

        expect($found->total_spend)->toBe(0.0);
    });

});

describe('createCategory', function (): void {

    // Line 75 — user has no household
    it('throws NotFoundHttpException when user has no household', function (): void {
        $user = User::factory()->create();
        $user->setRelation('household', null);

        expect(fn () => (new CategoryService)->createCategory($user, [
            'name' => 'Groceries',
            'type' => CategoryType::Expense->value,
        ]))->toThrow(NotFoundHttpException::class, 'User does not belong to a household.');
    });

    it('creates a category for the household', function (): void {
        $owner = User::factory()->create();
        $household = householdWithRoles([['user' => $owner, 'role' => HouseholdMemberRole::Owner]]);
        $owner->load('household');

        $category = (new CategoryService)->createCategory($owner, [
            'name' => 'Rent',
            'type' => CategoryType::Expense->value,
        ]);

        expect($category->name)->toBe('Rent')
            ->and($category->household_id)->toBe($household->id);
    });

    it('throws ConflictHttpException when duplicate name+type exists in household', function (): void {
        $owner = User::factory()->create();
        $household = householdWithRoles([['user' => $owner, 'role' => HouseholdMemberRole::Owner]]);
        $owner->load('household');

        categoryFor($household, $owner, ['name' => 'Rent', 'type' => CategoryType::Expense]);

        expect(fn () => (new CategoryService)->createCategory($owner, [
            'name' => 'Rent',
            'type' => CategoryType::Expense->value,
        ]))->toThrow(ConflictHttpException::class);
    });

    it('allows same name with different type', function (): void {
        $owner = User::factory()->create();
        $household = householdWithRoles([['user' => $owner, 'role' => HouseholdMemberRole::Owner]]);
        $owner->load('household');

        categoryFor($household, $owner, ['name' => 'Salary', 'type' => CategoryType::Expense]);

        $category = (new CategoryService)->createCategory($owner, [
            'name' => 'Salary',
            'type' => CategoryType::Income->value,
        ]);

        expect($category)->not->toBeNull();
    });

});

describe('updateCategory', function (): void {

    it('allows the creator to update their own category', function (): void {
        $owner = User::factory()->create();
        $household = householdWithRoles([['user' => $owner, 'role' => HouseholdMemberRole::Owner]]);
        $owner->load('household');

        $category = categoryFor($household, $owner, ['name' => 'Old']);
        $category->load('household');

        $updated = (new CategoryService)->updateCategory($owner, $category, ['name' => 'New']);

        expect($updated->name)->toBe('New');
    });

    it('allows a Member-role user to update a category they did not create', function (): void {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $household = householdWithRoles([
            ['user' => $owner,  'role' => HouseholdMemberRole::Owner],
            ['user' => $member, 'role' => HouseholdMemberRole::Member],
        ]);
        $owner->load('household');
        $member->load('household');

        $category = categoryFor($household, $owner, ['name' => 'Utilities']);
        $category->load('household');

        $updated = (new CategoryService)->updateCategory($member, $category, ['name' => 'Bills']);

        expect($updated->name)->toBe('Bills');
    });

    // Line 125 — Viewer role blocked in ensureCanManageCategory
    it('throws AccessDeniedHttpException when a Viewer tries to update a category', function (): void {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $household = householdWithRoles([
            ['user' => $owner,  'role' => HouseholdMemberRole::Owner],
            ['user' => $viewer, 'role' => HouseholdMemberRole::Viewer],
        ]);
        $owner->load('household');
        $viewer->load('household');

        $category = categoryFor($household, $owner);
        $category->load('household');

        expect(fn () => (new CategoryService)->updateCategory($viewer, $category, ['name' => 'Hacked']))
            ->toThrow(AccessDeniedHttpException::class, 'You do not have permission to manage this category.');
    });

    it('throws ConflictHttpException when changing type of a category with transactions', function (): void {
        $owner = User::factory()->create();
        $household = householdWithRoles([['user' => $owner, 'role' => HouseholdMemberRole::Owner]]);
        $owner->load('household');

        $account = Account::factory()->create(['user_id' => $owner->id, 'balance' => 500]);
        $category = categoryFor($household, $owner, ['type' => CategoryType::Expense]);
        $category->load('household');

        Transaction::factory()->create([
            'category_id' => $category->id,
            'account_id' => $account->id,
            'spender_user_id' => $owner->id,
        ]);

        expect(fn () => (new CategoryService)->updateCategory($owner, $category, [
            'type' => CategoryType::Income->value,
        ]))->toThrow(ConflictHttpException::class, 'Cannot modify or delete a category that has associated transactions.');
    });

    it('throws ConflictHttpException when updating to an existing name+type in household', function (): void {
        $owner = User::factory()->create();
        $household = householdWithRoles([['user' => $owner, 'role' => HouseholdMemberRole::Owner]]);
        $owner->load('household');

        categoryFor($household, $owner, ['name' => 'Existing', 'type' => CategoryType::Expense]);
        $category = categoryFor($household, $owner, ['name' => 'Other', 'type' => CategoryType::Expense]);
        $category->load('household');

        expect(fn () => (new CategoryService)->updateCategory($owner, $category, ['name' => 'Existing']))
            ->toThrow(ConflictHttpException::class);
    });

});

describe('deleteCategory', function (): void {

    it('allows the creator to delete their own category', function (): void {
        $owner = User::factory()->create();
        $household = householdWithRoles([['user' => $owner, 'role' => HouseholdMemberRole::Owner]]);
        $owner->load('household');

        $category = categoryFor($household, $owner);
        $category->load('household');

        (new CategoryService)->deleteCategory($owner, $category);

        assertSoftDeleted('categories', ['id' => $category->id]);
    });

    it('throws AccessDeniedHttpException when a Viewer tries to delete a category', function (): void {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $household = householdWithRoles([
            ['user' => $owner,  'role' => HouseholdMemberRole::Owner],
            ['user' => $viewer, 'role' => HouseholdMemberRole::Viewer],
        ]);
        $owner->load('household');

        $category = categoryFor($household, $owner);
        $category->load('household');

        expect(fn () => (new CategoryService)->deleteCategory($viewer, $category))
            ->toThrow(AccessDeniedHttpException::class);
    });

    // Line 142 via deleteCategory → ensureNoTransactions
    it('throws ConflictHttpException when deleting a category that has transactions', function (): void {
        $owner = User::factory()->create();
        $household = householdWithRoles([['user' => $owner, 'role' => HouseholdMemberRole::Owner]]);
        $owner->load('household');

        $account = Account::factory()->create(['user_id' => $owner->id, 'balance' => 500]);
        $category = categoryFor($household, $owner);
        $category->load('household');

        Transaction::factory()->create([
            'category_id' => $category->id,
            'account_id' => $account->id,
            'spender_user_id' => $owner->id,
        ]);

        expect(fn () => (new CategoryService)->deleteCategory($owner, $category))
            ->toThrow(ConflictHttpException::class, 'Cannot modify or delete a category that has associated transactions.');
    });

});
