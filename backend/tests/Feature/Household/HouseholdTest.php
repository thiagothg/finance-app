<?php

use App\Enums\HouseholdMemberRole;
use App\Enums\HouseholdMemberStatus;
use App\Models\Category;
use App\Models\Household;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\HouseholdInvitationNotification;
use App\Services\HouseholdService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('user can list their household with member spending', function () {
    /** @var TestCase $this */
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $household = Household::factory()->create(['owner_id' => $user1->id]);
    $household->members()->create(['user_id' => $user1->id, 'role' => HouseholdMemberRole::Owner]);
    $household->members()->create(['user_id' => $user2->id, 'role' => HouseholdMemberRole::Member]);

    $category = Category::factory()->create(['household_id' => $household->id]);

    Transaction::factory()->create(['category_id' => $category->id, 'spender_user_id' => $user1->id, 'amount' => 100.50]);
    Transaction::factory()->create(['category_id' => $category->id, 'spender_user_id' => $user2->id, 'amount' => 50.25]);

    $response = $this->actingAs($user1)->getJson('/api/v1/households');

    $response->assertStatus(200)
        ->assertJsonPath('meta.total_count', 1)
        ->assertJsonPath('data.0.id', $household->id)
        ->assertJsonPath('data.0.members_count', 2)
        ->assertJsonPath('data.0.current_user_membership.user_id', $user1->id)
        ->assertJsonPath('data.0.current_user_membership.role', HouseholdMemberRole::Owner->value)
        ->assertJsonPath('data.0.current_user_membership.status', HouseholdMemberStatus::Accepted->value);

    $data = $response->json('data.0.members');

    $user1Spend = collect($data)->firstWhere('user.id', $user1->id)['total_spend'];
    $user2Spend = collect($data)->firstWhere('user.id', $user2->id)['total_spend'];
    $user1Status = collect($data)->firstWhere('user.id', $user1->id)['status'];
    $user2Status = collect($data)->firstWhere('user.id', $user2->id)['status'];

    expect((float) $user1Spend)->toBe(100.50);
    expect((float) $user2Spend)->toBe(50.25);
    expect($user1Status)->toBe(HouseholdMemberStatus::Accepted->value);
    expect($user2Status)->toBe(HouseholdMemberStatus::Accepted->value);
});

test('user cannot create multiple households', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();
    $household = Household::factory()->create(['owner_id' => $user->id]);
    $household->members()->create(['user_id' => $user->id, 'role' => HouseholdMemberRole::Owner]);

    $response = $this->actingAs($user)->postJson('/api/v1/households', ['name' => 'Second Home']);

    $response->assertStatus(409);
});

test('user can create a household if they have none', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/households', ['name' => 'My Home']);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'My Home')
        ->assertJsonPath('data.owner_id', $user->id);

    $this->assertDatabaseHas('households', ['name' => 'My Home']);
    $this->assertDatabaseHas('household_members', ['user_id' => $user->id, 'role' => HouseholdMemberRole::Owner->value]);
});

test('member can update household name but viewer cannot', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $viewer = User::factory()->create();

    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);
    $household->members()->create(['user_id' => $member->id, 'role' => HouseholdMemberRole::Member]);
    $household->members()->create(['user_id' => $viewer->id, 'role' => HouseholdMemberRole::Viewer]);

    $this->actingAs($member)
        ->putJson("/api/v1/households/{$household->id}", ['name' => 'Updated By Member'])
        ->assertStatus(200);

    $this->assertDatabaseHas('households', ['id' => $household->id, 'name' => 'Updated By Member']);

    $this->actingAs($viewer)
        ->putJson("/api/v1/households/{$household->id}", ['name' => 'Updated By Viewer'])
        ->assertStatus(403);
});

test('add members enforces single household constraint and permissions', function () {
    /** @var TestCase $this */
    Notification::fake();

    $owner = User::factory()->create();
    $viewerUser = User::factory()->create();
    $newMemberEmail = 'newmember@example.com';

    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);
    $household->members()->create(['user_id' => $viewerUser->id, 'role' => HouseholdMemberRole::Viewer]);

    $this->actingAs($viewerUser)
        ->postJson("/api/v1/households/{$household->id}/members", [
            'name' => 'New Member',
            'email' => $newMemberEmail,
            'role' => HouseholdMemberRole::Member->value,
        ])
        ->assertForbidden();

    $this->actingAs($owner)
        ->postJson("/api/v1/households/{$household->id}/members", [
            'name' => 'New Member',
            'email' => $newMemberEmail,
            'role' => HouseholdMemberRole::Member->value,
        ])
        ->assertNoContent();

    $createdUser = User::where('email', $newMemberEmail)->first();
    expect($createdUser)->not->toBeNull();
    $this->assertDatabaseHas('household_members', [
        'user_id' => $createdUser->id,
        'role' => HouseholdMemberRole::Member->value,
        'status' => HouseholdMemberStatus::Pending->value,
    ]);

    Notification::assertSentTo($createdUser, HouseholdInvitationNotification::class);

    $thirdUser = User::factory()->create();
    $secondHousehold = Household::factory()->create(['owner_id' => $thirdUser->id]);
    $secondHousehold->members()->create(['user_id' => $thirdUser->id, 'role' => HouseholdMemberRole::Owner]);

    $this->actingAs($owner)
        ->postJson("/api/v1/households/{$household->id}/members", [
            'name' => $thirdUser->name,
            'email' => $thirdUser->email,
            'role' => HouseholdMemberRole::Member->value,
        ])
        ->assertStatus(409);

    $this->assertDatabaseHas('household_members', [
        'household_id' => $household->id,
        'user_id' => $viewerUser->id,
        'status' => HouseholdMemberStatus::Accepted->value,
    ]);
});

test('can resend a pending invitation and rejects non-pending members', function () {
    /** @var TestCase $this */
    Notification::fake();

    $owner = User::factory()->create();
    $pendingUser = User::factory()->create();
    $acceptedUser = User::factory()->create();
    $viewer = User::factory()->create();

    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);
    $household->members()->create([
        'user_id' => $pendingUser->id,
        'role' => HouseholdMemberRole::Member,
        'status' => HouseholdMemberStatus::Pending,
    ]);
    $household->members()->create([
        'user_id' => $acceptedUser->id,
        'role' => HouseholdMemberRole::Member,
        'status' => HouseholdMemberStatus::Accepted,
    ]);
    $household->members()->create([
        'user_id' => $viewer->id,
        'role' => HouseholdMemberRole::Viewer,
    ]);

    $this->actingAs($owner)
        ->postJson("/api/v1/households/{$household->id}/members/{$pendingUser->id}/resend-invitation")
        ->assertNoContent();

    Notification::assertSentToTimes($pendingUser, HouseholdInvitationNotification::class, 1);

    $this->actingAs($owner)
        ->postJson("/api/v1/households/{$household->id}/members/{$acceptedUser->id}/resend-invitation")
        ->assertStatus(409);

    $this->actingAs($viewer)
        ->postJson("/api/v1/households/{$household->id}/members/{$pendingUser->id}/resend-invitation")
        ->assertForbidden();
});

test('remove members enforces permissions and preserves owner', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $member1 = User::factory()->create();
    $member2 = User::factory()->create();

    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);
    $household->members()->create(['user_id' => $member1->id, 'role' => HouseholdMemberRole::Member]);
    $household->members()->create(['user_id' => $member2->id, 'role' => HouseholdMemberRole::Member]);

    $this->actingAs($member1)
        ->deleteJson("/api/v1/households/{$household->id}/members/{$member1->id}")
        ->assertStatus(204);

    $this->assertDatabaseMissing('household_members', ['user_id' => $member1->id, 'household_id' => $household->id]);

    $this->actingAs($member2)
        ->deleteJson("/api/v1/households/{$household->id}/members/{$owner->id}")
        ->assertStatus(403);

    $this->actingAs($owner)
        ->deleteJson("/api/v1/households/{$household->id}/members/{$member2->id}")
        ->assertStatus(204);

    $this->assertDatabaseMissing('household_members', ['user_id' => $member2->id, 'household_id' => $household->id]);
});

test('user without household receives empty household list', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/v1/households')
        ->assertStatus(200)
        ->assertJsonPath('meta.total_count', 0);
});

test('listMembers service method returns members collection', function () {
    $owner = User::factory()->create();
    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);

    $service = app(HouseholdService::class);
    $members = $service->listMembers($household);

    expect($members)->toHaveCount(1);
    expect($members->first()->user_id)->toBe($owner->id);
});

test('viewer cannot remove members', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $member = User::factory()->create();

    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);
    $household->members()->create(['user_id' => $viewer->id, 'role' => HouseholdMemberRole::Viewer]);
    $household->members()->create(['user_id' => $member->id, 'role' => HouseholdMemberRole::Member]);

    $this->actingAs($viewer)
        ->deleteJson("/api/v1/households/{$household->id}/members/{$member->id}")
        ->assertStatus(403);
});

test('removing non-existent member returns 404', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $alien = User::factory()->create();

    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);

    $this->actingAs($owner)
        ->deleteJson("/api/v1/households/{$household->id}/members/{$alien->id}")
        ->assertStatus(404);
});

test('user model relationships work', function () {
    $user = User::factory()->create();
    expect($user->transactions())->toBeInstanceOf(HasMany::class);
    expect($user->categories())->toBeInstanceOf(HasMany::class);
});

test('can list household members', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $household = Household::factory()->create(['owner_id' => $owner->id]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);

    $member = User::factory()->create();
    $household->members()->create(['user_id' => $member->id, 'role' => HouseholdMemberRole::Member]);

    $viewer = User::factory()->create();
    $household->members()->create([
        'user_id' => $viewer->id,
        'role' => HouseholdMemberRole::Viewer,
        'status' => HouseholdMemberStatus::Pending,
    ]);

    $response = $this->actingAs($owner)->getJson("/api/v1/households/{$household->id}/members");

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');

    $members = collect($response->json('data'))->keyBy('user.id');

    expect($members->get($owner->id)['status'])->toBe(HouseholdMemberStatus::Accepted->value)
        ->and($members->get($member->id)['status'])->toBe(HouseholdMemberStatus::Accepted->value)
        ->and($members->get($viewer->id)['status'])->toBe(HouseholdMemberStatus::Pending->value);
});
