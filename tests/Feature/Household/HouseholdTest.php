<?php

use App\Enums\HouseholdMemberRole;
use App\Models\Category;
use App\Models\Household;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can list their household with member spending', function () {
    /** @var \Tests\TestCase $this */
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $household = Household::factory()->create(['owner_id' => $user1->id]);
    $household->members()->create(['user_id' => $user1->id, 'role' => HouseholdMemberRole::Owner]);
    $household->members()->create(['user_id' => $user2->id, 'role' => HouseholdMemberRole::Member]);

    $category = Category::factory()->create(['household_id' => $household->id]);

    Transaction::factory()->create(['category_id' => $category->id, 'spender_user_id' => $user1->id, 'amount' => 100.50]);
    Transaction::factory()->create(['category_id' => $category->id, 'spender_user_id' => $user2->id, 'amount' => 50.25]);

    $response = $this->actingAs($user1)->getJson('/api/households');

    $response->assertStatus(200)
        ->assertJsonPath('meta.total_count', 1)
        ->assertJsonPath('data.0.id', $household->id)
        ->assertJsonPath('data.0.members_count', 2);

    $data = $response->json('data.0.members');
    $user1Spend = collect($data)->firstWhere('user.id', $user1->id)['total_spend'];
    $user2Spend = collect($data)->firstWhere('user.id', $user2->id)['total_spend'];

    expect((float) $user1Spend)->toBe(100.50);
    expect((float) $user2Spend)->toBe(50.25);
});

test('user cannot create multiple households', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create();
    $household = Household::factory()->create(['owner_id' => $user->id]);
    $household->members()->create(['user_id' => $user->id, 'role' => HouseholdMemberRole::Owner]);

    $response = $this->actingAs($user)->postJson('/api/households', ['name' => 'Second Home']);

    $response->assertStatus(409);
});

test('user can create a household if they have none', function () {
    /** @var \Tests\TestCase $this */
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/households', ['name' => 'My Home']);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'My Home')
        ->assertJsonPath('data.owner_id', $user->id);

    $this->assertDatabaseHas('households', ['name' => 'My Home']);
    $this->assertDatabaseHas('household_members', ['user_id' => $user->id, 'role' => HouseholdMemberRole::Owner->value]);
});

test('member can update household name but viewer cannot', function () {
    /** @var \Tests\TestCase $this */
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $viewer = User::factory()->create();

    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);
    $household->members()->create(['user_id' => $member->id, 'role' => HouseholdMemberRole::Member]);
    $household->members()->create(['user_id' => $viewer->id, 'role' => HouseholdMemberRole::Viewer]);

    $this->actingAs($member)
        ->putJson("/api/households/{$household->id}", ['name' => 'Updated By Member'])
        ->assertStatus(200);

    $this->assertDatabaseHas('households', ['id' => $household->id, 'name' => 'Updated By Member']);

    $this->actingAs($viewer)
        ->putJson("/api/households/{$household->id}", ['name' => 'Updated By Viewer'])
        ->assertStatus(403);
});

test('add members enforces single household constraint and permissions', function () {
    /** @var \Tests\TestCase $this */
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $viewerUser = User::factory()->create();

    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);
    $household->members()->create(['user_id' => $viewerUser->id, 'role' => HouseholdMemberRole::Viewer]);

    $this->actingAs($viewerUser)
        ->postJson("/api/households/{$household->id}/members", [
            'user_id' => $otherUser->id,
            'role' => HouseholdMemberRole::Member->value,
        ])
        ->assertStatus(403);

    $this->actingAs($owner)
        ->postJson("/api/households/{$household->id}/members", [
            'user_id' => $otherUser->id,
            'role' => HouseholdMemberRole::Member->value,
        ])
        ->assertStatus(204);

    $this->assertDatabaseHas('household_members', ['user_id' => $otherUser->id, 'role' => HouseholdMemberRole::Member->value]);

    $thirdUser = User::factory()->create();
    $secondHousehold = Household::factory()->create(['owner_id' => $thirdUser->id]);
    $secondHousehold->members()->create(['user_id' => $thirdUser->id, 'role' => HouseholdMemberRole::Owner]);

    $this->actingAs($owner)
        ->postJson("/api/households/{$household->id}/members", [
            'user_id' => $thirdUser->id,
            'role' => HouseholdMemberRole::Member->value,
        ])
        ->assertStatus(409);
});

test('remove members enforces permissions and preserves owner', function () {
    /** @var \Tests\TestCase $this */
    $owner = User::factory()->create();
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();

    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);
    $household->members()->create(['user_id' => $member1->id, 'role' => HouseholdMemberRole::Member]);
    $household->members()->create(['user_id' => $member2->id, 'role' => HouseholdMemberRole::Member]);

    $this->actingAs($member1)
        ->deleteJson("/api/households/{$household->id}/members/{$member1->id}")
        ->assertStatus(204);

    $this->assertDatabaseMissing('household_members', ['user_id' => $member1->id, 'household_id' => $household->id]);

    $this->actingAs($member2)
        ->deleteJson("/api/households/{$household->id}/members/{$owner->id}")
        ->assertStatus(403);

    $this->actingAs($owner)
        ->deleteJson("/api/households/{$household->id}/members/{$member2->id}")
        ->assertStatus(204);

    $this->assertDatabaseMissing('household_members', ['user_id' => $member2->id, 'household_id' => $household->id]);
});
