<?php

namespace App\Repositories;

use App\Models\User;

class AuthRepository
{
    /**
     * Find a user by email address.
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Create a new user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data): User
    {
        return User::create($data);
    }

    /**
     * Delete all tokens for a user.
     */
    public function deleteUserTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}
