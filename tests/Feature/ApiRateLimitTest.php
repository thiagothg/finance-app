<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

function testUser(): Authenticatable
{
    $user = User::factory()->create();

    RateLimiter::clear('api|'.$user->getAuthIdentifier());

    return $user;
}

describe('api rate limiting', function (): void {
    it('blocks the 11th login attempt with a custom message', function (): void {
        foreach (range(1, 10) as $_) {
            postJson('/api/v1/auth/login', [
                'email' => 'user@test.com',
                'password' => 'wrong',
            ]);
        }

        $response = postJson('/api/v1/auth/login', [
            'email' => 'user@test.com',
            'password' => 'wrong',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('message', 'Too many login attempts. Please try again in a minute.');
    });

    it('blocks the 61st request with a custom message', function (): void {
        $user = testUser();

        foreach (range(1, 60) as $_) {
            actingAs($user)->getJson('/api/v1/accounts');
        }

        $response = actingAs($user)
            ->getJson('/api/v1/accounts');

        $response->assertStatus(429)
            ->assertJsonPath('message', 'Too many requests. Please slow down.');
    });

    it('allows requests within the 60 req/min limit', function (): void {
        $user = testUser();
        foreach (range(1, 5) as $attempt) {
            $response = actingAs($user)
                ->getJson('/api/v1/accounts');

            $response->assertOk()
                ->assertHeader('X-RateLimit-Limit', '60')
                ->assertHeader('X-RateLimit-Remaining', (string) (60 - $attempt));
        }
    });

    it('blocks requests after exceeding 60 req/min', function (): void {
        $user = testUser();
        foreach (range(1, 60) as $_) {
            actingAs($user)->getJson('/api/v1/accounts');
        }

        $response = actingAs($user)->getJson('/api/v1/accounts');

        $response->assertStatus(429)
            ->assertJsonPath('message', 'Too many requests. Please slow down.');
    });

    it('keys the limit by user id, not ip — different users are independent', function (): void {
        $user = testUser();
        /** @var User $otherUser */
        $otherUser = User::factory()->create();

        // Exhaust limit for $user
        foreach (range(1, 60) as $_) {
            actingAs($user)->getJson('/api/v1/accounts');
        }

        // Other user should still be able to make requests
        $response = actingAs($otherUser)->getJson('/api/v1/accounts');

        $response->assertOk();
    });

    it('returns 401 instead of 429 for unauthenticated requests', function (): void {
        $response = getJson('/api/v1/accounts');

        $response->assertUnauthorized();
    });

    it('apply api rate limit to the health endpoint', function (): void {
        foreach (range(1, 60) as $_) {
            getJson('/api/v1/health')->assertOk();
        }

        $response = getJson('/api/v1/health');

        $response->assertStatus(429)
            ->assertJsonPath('message', 'Too many requests. Please slow down.');
    });

});
