<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

final readonly class AuthService
{
    /**
     * Register a new user and return a token pair.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, access_token: string, refresh_token: string, access_expires_at: Carbon, refresh_expires_at: Carbon}
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return $this->createTokenPair($user);
    }

    /**
     * Validate credentials and return a token pair.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, access_token: string, refresh_token: string, access_expires_at: Carbon, refresh_expires_at: Carbon}
     *
     * @throws ValidationException
     */
    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Revoke all existing tokens on fresh login for security.
        $user->tokens()->delete();

        return $this->createTokenPair($user);
    }

    /**
     * Revoke the current access token.
     */
    public function logout(User $user): void
    {
        /** @var PersonalAccessToken $token */
        $token = $user->currentAccessToken();
        $token->delete();
    }

    /**
     * Refresh a token pair using the provided refresh token string.
     *
     * Looks up the hashed token in personal_access_tokens, verifies it
     * carries the 'token:refresh' ability, checks expiration, and applies
     * the silent-renewal grace window before issuing a new pair.
     *
     * @return array{user: User, access_token: string, refresh_token: string, access_expires_at: Carbon, refresh_expires_at: Carbon}
     *
     * @throws ValidationException
     */
    public function refresh(string $rawRefreshToken): array
    {
        // Sanctum stores tokens as: id|plaintext — hash the plaintext part.
        [$id, $token] = explode('|', $rawRefreshToken, 2);

        /** @var PersonalAccessToken|null $tokenModel */
        $tokenModel = PersonalAccessToken::find((int) $id);

        if (! $tokenModel || ! hash_equals($tokenModel->token, hash('sha256', $token))) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Invalid refresh token.'],
            ]);
        }

        if (! in_array('token:refresh', $tokenModel->abilities)) {
            throw ValidationException::withMessages([
                'refresh_token' => ['Token is not a valid refresh token.'],
            ]);
        }

        $isExpired = $tokenModel->expires_at && $tokenModel->expires_at->isPast();

        if ($isExpired) {
            $lastUsed = $tokenModel->last_used_at;
            $withinGrace = $lastUsed && $lastUsed->isAfter(now()->subDays((int) config('sanctum.silent_renew_grace_days')));

            if (! $withinGrace) {
                // Token expired and inactive — force re-login.
                $tokenModel->delete();

                throw ValidationException::withMessages([
                    'refresh_token' => ['Session expired. Please log in again.'],
                ]);
            }
            // Token expired but user was recently active — silent renewal proceeds.
        }

        $user = $tokenModel->tokenable;

        // Revoke the used refresh token and all existing access tokens.
        $user->tokens()->delete();

        return $this->createTokenPair($user);
    }

    /**
     * Create a short-lived access token and a long-lived refresh token.
     *
     * Access token  → ability ['*'],             expires in 15 minutes.
     * Refresh token → ability ['token:refresh'], expires in 30 days.
     *
     * @return array{user: User, access_token: string, refresh_token: string, access_expires_at: Carbon, refresh_expires_at: Carbon}
     */
    public function createTokenPair(User $user): array
    {
        $accessExpiresAt = now()->addMinutes((int) config('sanctum.access_token_ttl_minutes'));
        $refreshExpiresAt = now()->addDays((int) config('sanctum.refresh_token_ttl_days'));

        /** @var NewAccessToken $accessToken */
        $accessToken = $user->createToken(
            name: 'access-token',
            abilities: ['*'],
            expiresAt: $accessExpiresAt,
        );

        /** @var NewAccessToken $refreshToken */
        $refreshToken = $user->createToken(
            name: 'refresh-token',
            abilities: ['token:refresh'],
            expiresAt: $refreshExpiresAt,
        );

        return [
            'user' => $user,
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
            'access_expires_at' => $accessExpiresAt,
            'refresh_expires_at' => $refreshExpiresAt,
        ];
    }
}
