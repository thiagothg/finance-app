<?php

declare(strict_types=1);

namespace App\Http\Resources\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read User  $user
 * @property-read string            $access_token
 * @property-read string            $refresh_token
 * @property-read Carbon   $access_expires_at
 * @property-read Carbon   $refresh_expires_at
 */
final class AuthResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var array{user: User, access_token: string, refresh_token: string, access_expires_at: Carbon, refresh_expires_at: Carbon} $data */
        $data = $this->resource;

        return [
            'user' => [
                'id' => $data['user']->id,
                'name' => $data['user']->name,
                'email' => $data['user']->email,
            ],
            'access_token' => $data['access_token'],
            'refresh_token' => $data['refresh_token'],
            'access_expires_at' => $data['access_expires_at']->toIso8601String(),
            'refresh_expires_at' => $data['refresh_expires_at']->toIso8601String(),
        ];
    }
}
