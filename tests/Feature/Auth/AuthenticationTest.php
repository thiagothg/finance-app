<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
use function Pest\Laravel\{postJson, withHeaders, assertDatabaseCount, assertDatabaseHas};

/*
|--------------------------------------------------------------------------
| Login Tests
|--------------------------------------------------------------------------
*/

test('user can login with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response = postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email'],
            'token',
        ]);
});

test('login fails with invalid credentials', function () {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response = postJson('/api/auth/login', [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('login fails without required fields', function () {
    $response = postJson('/api/auth/login', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

/*
|--------------------------------------------------------------------------
| Register Tests
|--------------------------------------------------------------------------
*/

test('user can register with valid data', function () {
    /** @var \Tests\TestCase $this */
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Test User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'message',
            'user' => ['id', 'name', 'email'],
            'token',
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
    ]);
});

test('register fails with duplicate email', function () {
    User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $response = postJson('/api/auth/register', [
        'name' => 'Test User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('register fails without required fields', function () {
    $response = postJson('/api/auth/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

/*
|--------------------------------------------------------------------------
| Logout Tests
|--------------------------------------------------------------------------
*/

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    $response = withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->postJson('/api/auth/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Logout successful.']);

   assertDatabaseCount('personal_access_tokens', 0);
});

test('unauthenticated user cannot logout', function () {
    $response = postJson('/api/auth/logout');

    $response->assertStatus(401);
});
