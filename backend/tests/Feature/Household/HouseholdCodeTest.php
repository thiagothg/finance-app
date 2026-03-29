<?php

use App\Enums\HouseholdMemberRole;
use App\Enums\HouseholdMemberStatus;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('household generates an 8-digit invitation code upon creation', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/households', [
        'name' => 'My New Home',
    ]);

    $response->assertStatus(201);

    $invitationCode = $response->json('data.invitation_code');

    expect($invitationCode)->not->toBeNull()
        ->and(strlen($invitationCode))->toBe(8)
        ->and(ctype_digit($invitationCode))->toBeTrue();

    $this->assertDatabaseHas('households', [
        'id' => $response->json('data.id'),
        'invitation_code' => $invitationCode,
    ]);
});

test('user can join a household using a valid invitation code', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $household = Household::factory()->create([
        'owner_id' => $owner,
        'invitation_code' => '12345678',
    ]);
    // The factory created above will need to exist in DB, and we need to ensure the owner is a member too if that's expected by the system.
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);

    $newUser = User::factory()->create();

    $response = $this->actingAs($newUser)->postJson('/api/v1/households/join', [
        'invitation_code' => '12345678',
    ]);

    $response->assertStatus(200);
    $response->assertJsonPath('data.id', $household->id);

    $this->assertDatabaseHas('household_members', [
        'household_id' => $household->id,
        'user_id' => $newUser->id,
        'role' => HouseholdMemberRole::Member->value,
        'status' => HouseholdMemberStatus::Accepted->value,
    ]);
});

test('user can accept a pending household invitation using the invitation code', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create();
    $household = Household::factory()->create([
        'owner_id' => $owner,
        'invitation_code' => '12345678',
    ]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);
    $household->members()->create([
        'user_id' => $invitedUser->id,
        'role' => HouseholdMemberRole::Member,
        'status' => HouseholdMemberStatus::Pending,
    ]);

    $response = $this->actingAs($invitedUser)->postJson('/api/v1/households/12345678/members/accept');

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $household->id);

    $this->assertDatabaseHas('household_members', [
        'household_id' => $household->id,
        'user_id' => $invitedUser->id,
        'status' => HouseholdMemberStatus::Accepted->value,
    ]);
});

test('user can decline a pending household invitation using the invitation code', function () {
    /** @var TestCase $this */
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create();
    $household = Household::factory()->create([
        'owner_id' => $owner,
        'invitation_code' => '87654321',
    ]);
    $household->members()->create(['user_id' => $owner->id, 'role' => HouseholdMemberRole::Owner]);
    $household->members()->create([
        'user_id' => $invitedUser->id,
        'role' => HouseholdMemberRole::Member,
        'status' => HouseholdMemberStatus::Pending,
    ]);

    $this->actingAs($invitedUser)
        ->postJson('/api/v1/households/87654321/members/decline')
        ->assertNoContent();

    $this->assertDatabaseMissing('household_members', [
        'household_id' => $household->id,
        'user_id' => $invitedUser->id,
    ]);
});

test('joining fails with invalid invitation code', function () {
    /** @var TestCase $this */
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson('/api/v1/households/join', [
        'invitation_code' => 'nonexistent',
    ]);

    $response->assertStatus(422) // Validation error for format
        ->assertJsonValidationErrors(['invitation_code']);

    $response = $this->actingAs($user)->postJson('/api/v1/households/join', [
        'invitation_code' => '99999999',
    ]);

    $response->assertStatus(404); // Not found error for non-existent code
});

test('user cannot join a household if they already belong to one', function () {
    /** @var TestCase $this */
    $owner1 = User::factory()->create();
    $household1 = Household::factory()->create(['owner_id' => $owner1, 'invitation_code' => '11111111']);
    $household1->members()->create(['user_id' => $owner1->id, 'role' => HouseholdMemberRole::Owner]);

    $owner2 = User::factory()->create();
    $household2 = Household::factory()->create(['owner_id' => $owner2, 'invitation_code' => '22222222']);
    $household2->members()->create(['user_id' => $owner2->id, 'role' => HouseholdMemberRole::Owner]);

    // owner2 tries to join household1
    $response = $this->actingAs($owner2)->postJson('/api/v1/households/join', [
        'invitation_code' => '11111111',
    ]);

    $response->assertStatus(409);
});
