<?php

use App\Models\User;
use App\Notifications\AccountValidationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

use function Pest\Faker\fake;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withHeaders;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Login Tests
|--------------------------------------------------------------------------
*/

test('user can login with valid credentials', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response = postJson('/api/v1/auth/login', [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'message',
            'email',
            'verification_expires_at',
        ])
        ->assertJsonPath('email', 'test@example.com')
        ->assertJsonPath('message', 'Verification code sent to your email.');

    Notification::assertSentTo($user, AccountValidationCodeNotification::class);

    $user->refresh();
    expect($user->validation_code)->not->toBeNull()
        ->and($user->validation_code_expires_at)->not->toBeNull();
});

test('login fails with invalid credentials', function () {
    $email = fake()->unique()->email;
    User::factory()->create([
        'email' => $email,
        'password' => 'password123',
    ]);

    $response = postJson('/api/v1/auth/login', [
        'email' => $email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('login fails without required fields', function () {
    $response = postJson('/api/v1/auth/login', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password']);
});

/*
|--------------------------------------------------------------------------
| Register Tests
|--------------------------------------------------------------------------
*/

test('user can register with valid data', function () {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'newuser@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email'],
                'access_token',
                'refresh_token',
                'access_expires_at',
                'refresh_expires_at',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
    ]);
});

test('register fails with duplicate email', function () {
    User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $response = postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('register fails without required fields', function () {
    $response = postJson('/api/v1/auth/register', []);

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
    ])->postJson('/api/v1/auth/logout');

    $response->assertSuccessful()
        ->assertJson(['message' => 'Logged out successfully.']);

    assertDatabaseCount('personal_access_tokens', 0);
});

test('unauthenticated user cannot logout', function () {
    $response = postJson('/api/v1/auth/logout');

    $response->assertStatus(401);
});
