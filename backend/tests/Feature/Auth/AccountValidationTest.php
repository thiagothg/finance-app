<?php

use App\Models\User;
use App\Notifications\AccountValidationCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('send validation code dispatches notification', function () {
    /** @var TestCase $this */
    Notification::fake();

    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/send-validation-code', [
        'email' => $user->email,
    ])->assertSuccessful();

    Notification::assertSentTo($user, AccountValidationCodeNotification::class);

    $user->refresh();
    expect($user->validation_code)->not->toBeNull();
    expect($user->validation_code_expires_at)->not->toBeNull();
});

test('resend validation code dispatches a fresh notification and rotates the code', function () {
    /** @var TestCase $this */
    Notification::fake();

    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/resend-validation-code', [
        'email' => $user->email,
    ])->assertSuccessful()
        ->assertJsonPath('message', 'Validation code resent.');

    Notification::assertSentTo($user, AccountValidationCodeNotification::class);

    $user->refresh();
    $firstHashedCode = $user->validation_code;
    $firstExpiresAt = $user->validation_code_expires_at;

    $this->travel(1)->second();

    $this->postJson('/api/v1/auth/resend-validation-code', [
        'email' => $user->email,
    ])->assertSuccessful()
        ->assertJsonPath('message', 'Validation code resent.');

    $user->refresh();

    expect($user->validation_code)->not->toBe($firstHashedCode)
        ->and($user->validation_code_expires_at?->greaterThan($firstExpiresAt))->toBeTrue();
});

test('validate code verifies account and returns tokens', function () {
    /** @var TestCase $this */
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $code = '123456';
    $user->update([
        'validation_code' => Hash::make($code),
        'validation_code_expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->postJson('/api/v1/auth/validate-code', [
        'email' => $user->email,
        'code' => $code,
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => [
                'access_token',
                'refresh_token',
            ],
        ]);

    $user->refresh();
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->validation_code)->toBeNull();
});

test('validate code rejects expired code', function () {
    /** @var TestCase $this */
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $code = '654321';
    $user->update([
        'validation_code' => Hash::make($code),
        'validation_code_expires_at' => now()->subMinutes(1),
    ]);

    $this->postJson('/api/v1/auth/validate-code', [
        'email' => $user->email,
        'code' => $code,
    ])->assertStatus(422);
});

test('validate code rejects wrong code', function () {
    /** @var TestCase $this */
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    $user->update([
        'validation_code' => Hash::make('111111'),
        'validation_code_expires_at' => now()->addMinutes(15),
    ]);

    $this->postJson('/api/v1/auth/validate-code', [
        'email' => $user->email,
        'code' => '999999',
    ])->assertStatus(422);
});

test('send validation code requires valid email', function () {
    /** @var TestCase $this */
    $this->postJson('/api/v1/auth/send-validation-code', [
        'email' => 'nonexistent@example.com',
    ])->assertStatus(422);
});
