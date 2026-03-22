<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(RefreshDatabase::class);

describe('register', function (): void {
    it('creates a user and returns a token pair', function (): void {
        $result = (new AuthService)->register([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ]);

        expect($result)->toHaveKeys(['user', 'access_token', 'refresh_token', 'access_expires_at', 'refresh_expires_at'])
            ->and($result['user']->email)->toBe('jane@example.com');

        assertDatabaseHas('users', ['email' => 'jane@example.com']);
    });

    it('stores the access token with 15-minute expiry', function (): void {
        $result = (new AuthService)->register([
            'name' => 'Test', 'email' => 'a@a.com', 'password' => 'secret',
        ]);

        expect($result['access_expires_at']->diffInMinutes(now()))->toBeLessThanOrEqual(15);
    });

    it('stores the refresh token with 30-day expiry', function (): void {
        $result = (new AuthService)->register([
            'name' => 'Test', 'email' => 'b@b.com', 'password' => 'secret',
        ]);

        expect($result['refresh_expires_at']->diffInDays(now()))->toBeLessThanOrEqual(30);
    });

    it('returns both tokens via the HTTP endpoint', function (): void {
        postJson('/api/v1/auth/register', [
            'name' => 'Jane',
            'email' => 'jane2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSuccessful()
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'access_token',
                    'refresh_token',
                    'access_expires_at',
                    'refresh_expires_at',
                ],
            ]);
    });

});

describe('login', function (): void {

    it('returns a token pair for valid credentials', function (): void {
        $user = User::factory()->create(['password' => bcrypt('secret')]);

        $result = (new AuthService)->login([
            'email' => $user->email,
            'password' => 'secret',
        ]);

        expect($result['user']->id)->toBe($user->id)
            ->and($result['access_token'])->not->toBeEmpty()
            ->and($result['refresh_token'])->not->toBeEmpty();
    });

    it('revokes all existing tokens on new login', function (): void {
        $user = User::factory()->create(['password' => bcrypt('secret')]);
        $user->createToken('old-token');

        expect(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(1);

        (new AuthService)->login(['email' => $user->email, 'password' => 'secret']);

        // Old token revoked, two new ones created (access + refresh).
        expect(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(2);
    });

    it('throws ValidationException for wrong password', function (): void {
        $user = User::factory()->create(['password' => bcrypt('correct')]);

        expect(fn () => (new AuthService)->login([
            'email' => $user->email,
            'password' => 'wrong',
        ]))->toThrow(ValidationException::class);
    });

    it('throws ValidationException for unknown email', function (): void {
        expect(fn () => (new AuthService)->login([
            'email' => 'nobody@example.com',
            'password' => 'whatever',
        ]))->toThrow(ValidationException::class);
    });

    it('access token carries the wildcard ability', function (): void {
        $user = User::factory()->create(['password' => bcrypt('secret')]);
        $result = (new AuthService)->login(['email' => $user->email, 'password' => 'secret']);

        [$id] = explode('|', $result['access_token']);
        $token = PersonalAccessToken::find((int) $id);

        /** @var PersonalAccessToken $token */
        expect($token->abilities)->toBe(['*']);
    });

    it('refresh token carries only the token:refresh ability', function (): void {
        $user = User::factory()->create(['password' => bcrypt('secret')]);
        $result = (new AuthService)->login(['email' => $user->email, 'password' => 'secret']);

        [$id] = explode('|', $result['refresh_token']);
        $token = PersonalAccessToken::find((int) $id);

        /** @var PersonalAccessToken $token */
        expect($token->can('token:refresh'))->toBeTrue()
            ->and($token->can('create:transaction'))->toBeFalse();
    });

});

describe('logout', function (): void {

    it('revokes the current access token', function (): void {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        expect(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(0);
    });

});

describe('refresh', function (): void {

    it('issues a new token pair when given a valid refresh token', function (): void {
        $user = User::factory()->create(['password' => bcrypt('secret')]);
        $tokens = (new AuthService)->login(['email' => $user->email, 'password' => 'secret']);

        $response = postJson('/api/v1/auth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['access_token', 'refresh_token', 'access_expires_at', 'refresh_expires_at'],
            ]);
    });

    it('revokes the old token pair after a successful refresh', function (): void {
        $user = User::factory()->create(['password' => bcrypt('secret')]);
        $tokens = (new AuthService)->login(['email' => $user->email, 'password' => 'secret']);

        $oldCount = PersonalAccessToken::where('tokenable_id', $user->id)->count();
        expect($oldCount)->toBe(2);

        postJson('/api/v1/auth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ])->assertOk();

        // Old pair replaced with new pair.
        expect(PersonalAccessToken::where('tokenable_id', $user->id)->count())->toBe(2);
    });

    it('rejects an access token used as refresh token', function (): void {
        $user = User::factory()->create(['password' => bcrypt('secret')]);
        $tokens = (new AuthService)->login(['email' => $user->email, 'password' => 'secret']);

        postJson('/api/v1/auth/refresh', [
            'refresh_token' => $tokens['access_token'], // wrong token type
        ])->assertUnprocessable()
            ->assertJsonPath('errors.refresh_token.0', 'Token is not a valid refresh token.');
    });

    it('rejects a tampered refresh token', function (): void {
        postJson('/api/v1/auth/refresh', [
            'refresh_token' => '1|totallyfaketoken',
        ])->assertUnprocessable()
            ->assertJsonPath('errors.refresh_token.0', 'Invalid refresh token.');
    });

    it('rejects an expired refresh token when user has been inactive', function (): void {
        $user = User::factory()->create(['password' => bcrypt('secret')]);
        $tokens = (new AuthService)->login(['email' => $user->email, 'password' => 'secret']);

        // Expire the refresh token and simulate old last_used_at.
        [$id] = explode('|', $tokens['refresh_token']);
        /** @var PersonalAccessToken $token */
        $token = PersonalAccessToken::find((int) $id);

        $token->forceFill([
            'expires_at' => now()->subDay(),
            'last_used_at' => now()->subDays(8), // outside the 7-day grace window
        ])->save();

        postJson('/api/v1/auth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ])->assertUnprocessable()
            ->assertJsonPath('errors.refresh_token.0', 'Session expired. Please log in again.');
    });

    it('silently renews when refresh token is expired but user was active within 7 days', function (): void {
        $user = User::factory()->create(['password' => bcrypt('secret')]);
        $tokens = (new AuthService)->login(['email' => $user->email, 'password' => 'secret']);

        [$id] = explode('|', $tokens['refresh_token']);
        /** @var PersonalAccessToken $token */
        $token = PersonalAccessToken::find((int) $id);

        $token->forceFill([
            'expires_at' => now()->subHour(),
            'last_used_at' => now()->subDays(3), // within grace window
        ])->save();

        postJson('/api/v1/auth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ])->assertOk()
            ->assertJsonStructure(['data' => ['access_token', 'refresh_token']]);
    });

    it('returns 422 when refresh_token field is missing', function (): void {
        postJson('/api/v1/auth/refresh', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['refresh_token']);
    });

    it('new access token from refresh is usable on protected routes', function (): void {
        $user = User::factory()->create(['password' => bcrypt('secret')]);
        $tokens = (new AuthService)->login(['email' => $user->email, 'password' => 'secret']);

        $refreshResponse = postJson('/api/v1/auth/refresh', [
            'refresh_token' => $tokens['refresh_token'],
        ]);

        $newAccessToken = $refreshResponse->json('data.access_token');

        withToken($newAccessToken)
            ->getJson('/api/v1/user/me')
            ->assertOk()
            ->assertJsonPath('id', $user->id);
    });

});
