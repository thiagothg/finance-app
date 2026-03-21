<?php

use App\Enums\CategoryType;
use App\Enums\HouseholdMemberRole;
use App\Models\Category;
use App\Models\Household;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('user can list categories with total spend grouped by type', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);

    $incomeCat = Category::factory()->create([
        'household_id' => $household->id,
        'user_id' => $owner->id,
        'type' => CategoryType::Income,
        'name' => 'Salary',
    ]);

    $expenseCat = Category::factory()->create([
        'household_id' => $household->id,
        'user_id' => $owner->id,
        'type' => CategoryType::Expense,
        'name' => 'Groceries',
    ]);

    // Add transactions to test spend aggregation
    Transaction::factory()->create(['category_id' => $incomeCat->id, 'spender_user_id' => $owner->id, 'amount' => 5000]);
    Transaction::factory()->create(['category_id' => $expenseCat->id, 'spender_user_id' => $owner->id, 'amount' => 150]);
    Transaction::factory()->create(['category_id' => $expenseCat->id, 'spender_user_id' => $owner->id, 'amount' => 50]);

    $response = $this->actingAs($owner)->getJson('/api/v1/categories');

    $response->assertStatus(200)
        ->assertJsonPath('meta.total_count', 2)
        ->assertJsonPath('data.Income.0.id', $incomeCat->id)
        ->assertJsonPath('data.Income.0.total_spend', 5000)
        ->assertJsonPath('data.Expense.0.id', $expenseCat->id)
        ->assertJsonPath('data.Expense.0.total_spend', 200); // 150 + 50
});

test('user can filter categories by type', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);

    Category::factory()->create(['household_id' => $household->id, 'user_id' => $owner->id, 'type' => CategoryType::Income, 'name' => 'Bonus']);
    Category::factory()->create(['household_id' => $household->id, 'user_id' => $owner->id, 'type' => CategoryType::Expense, 'name' => 'Rent']);

    $response = $this->actingAs($owner)->getJson('/api/v1/categories?type='.CategoryType::Income->value);

    $response->assertStatus(200)
        ->assertJsonPath('meta.total_count', 1)
        ->assertJsonMissing(['data' => ['Expense' => []]]);
});

test('user can create a category with budget', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);

    $response = $this->actingAs($owner)->postJson('/api/v1/categories', [
        'name' => 'Utilities',
        'type' => CategoryType::Expense->value,
        'icon' => 'bolt',
        'color' => '#FF0000',
        'budget' => 300.50,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Utilities')
        ->assertJsonPath('data.budget', 300.50)
        ->assertJsonPath('data.user_id', $owner->id);

    $this->assertDatabaseHas('categories', [
        'name' => 'Utilities',
        'household_id' => $household->id,
        'budget' => 300.50,
    ]);
});

test('creating category with duplicate name and type within same household fails', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);

    Category::factory()->create([
        'household_id' => $household->id,
        'user_id' => $owner->id,
        'type' => CategoryType::Expense,
        'name' => 'Dining',
    ]);

    $response = $this->actingAs($owner)->postJson('/api/v1/categories', [
        'name' => 'Dining',
        'type' => CategoryType::Expense->value,
        'icon' => 'food',
        'color' => '#000',
    ]);

    $response->assertStatus(409); // Conflict
});

test('updating category type when transactions exist fails', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);

    $cat = Category::factory()->create(['household_id' => $household->id, 'user_id' => $owner->id, 'type' => CategoryType::Income]);
    Transaction::factory()->create(['category_id' => $cat->id, 'spender_user_id' => $owner->id]);

    $response = $this->actingAs($owner)->putJson("/api/v1/categories/{$cat->id}", [
        'name' => 'Changed Name',
        'type' => CategoryType::Expense->value,
        'icon' => 'food',
        'color' => '#000',
        'budget' => 300.50,
    ]);

    $response->assertStatus(409); // Conflict
});

test('updating category name and budget when transactions exist succeeds', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);

    $cat = Category::factory()->create(['household_id' => $household->id, 'user_id' => $owner->id, 'type' => CategoryType::Expense]);
    Transaction::factory()->create(['category_id' => $cat->id, 'spender_user_id' => $owner->id]);

    $response = $this->actingAs($owner)->putJson("/api/v1/categories/{$cat->id}", [
        'name' => 'New Awesome Name',
        'type' => CategoryType::Expense->value,
        'icon' => 'food',
        'color' => '#000',
        'budget' => 500.00,
    ]);

    $response->assertStatus(200);
});

test('deleting category when transactions exist fails', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);

    $cat = Category::factory()->create(['household_id' => $household->id, 'user_id' => $owner->id]);
    Transaction::factory()->create(['category_id' => $cat->id, 'spender_user_id' => $owner->id]);

    $response = $this->actingAs($owner)->deleteJson("/api/v1/categories/{$cat->id}");

    $response->assertStatus(409); // Conflict
});

test('only owner/member or creator can update category', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $viewer = User::factory()->create();
    $alien = User::factory()->create();

    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);
    $household->members()->create(['user_id' => $member->id, 'role' => HouseholdMemberRole::Member]);
    $household->members()->create(['user_id' => $viewer->id, 'role' => HouseholdMemberRole::Viewer]);

    // Created by owner
    $catByOwner = Category::factory()->create(['household_id' => $household->id, 'user_id' => $owner->id]);

    // Viewer tries to edit owner's category -> fails

    $this->actingAs($viewer)
        ->putJson("/api/v1/categories/{$catByOwner->id}", ['name' => 'Hacked', 'type' => CategoryType::Expense->value])
        ->assertStatus(403);

    $this->actingAs($alien)
        ->putJson("/api/v1/categories/{$catByOwner->id}", ['name' => 'Hacked', 'type' => CategoryType::Expense->value])
        ->assertStatus(403);

    $this->actingAs($member)
        ->putJson("/api/v1/categories/{$catByOwner->id}", ['name' => 'Updated by Member', 'type' => CategoryType::Expense->value])
        ->assertStatus(200);

    $catByViewer = Category::factory()->create(['household_id' => $household->id, 'user_id' => $viewer->id]);

    $this->actingAs($viewer)
        ->putJson("/api/v1/categories/{$catByViewer->id}", ['name' => 'Viewer Fixed', 'type' => CategoryType::Expense->value])
        ->assertStatus(200);
});

test('user without household receives empty categories list', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();

    $this->actingAs($owner)->getJson('/api/v1/categories')
        ->assertStatus(200)
        ->assertJsonPath('meta.total_count', 0);
});

test('user with household but no categories receives empty list', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);

    $this->actingAs($owner)->getJson('/api/v1/categories')
        ->assertStatus(200)
        ->assertJsonPath('meta.total_count', 0);
});

test('user without household cannot create category', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();

    $this->actingAs($owner)->postJson('/api/v1/categories', [
        'name' => 'Test', 'type' => CategoryType::Expense->value, 'icon' => 'test', 'color' => '#000',
    ])->assertStatus(404);
});

test('updating category to existing duplicate fails', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);

    $cat1 = Category::factory()->create(['household_id' => $household->id, 'user_id' => $owner->id, 'name' => 'A', 'type' => CategoryType::Expense]);
    $cat2 = Category::factory()->create(['household_id' => $household->id, 'user_id' => $owner->id, 'name' => 'B', 'type' => CategoryType::Expense]);

    $this->actingAs($owner)->putJson("/api/v1/categories/{$cat2->id}", [
        'name' => 'A',
        'type' => CategoryType::Expense->value,
        'icon' => 'food',
        'color' => '#000',
    ])->assertStatus(409);
});

test('deleting category when no transactions exist succeeds', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);

    $cat = Category::factory()->create(['household_id' => $household->id, 'user_id' => $owner->id]);

    $this->actingAs($owner)->deleteJson("/api/v1/categories/{$cat->id}")
        ->assertStatus(204);
});

test('can show a category', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);

    $cat = Category::factory()->create(['household_id' => $household->id, 'user_id' => $owner->id]);

    $this->actingAs($owner)->getJson("/api/v1/categories/{$cat->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.id', $cat->id);
});
