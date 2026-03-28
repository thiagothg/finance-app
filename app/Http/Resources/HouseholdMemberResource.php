<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\HouseholdMemberRole;
use App\Enums\HouseholdMemberStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property HouseholdMemberRole $role
 * @property HouseholdMemberStatus $status
 * @property User $user
 * @property float $total_spend
 */
class HouseholdMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'status' => $this->status,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'total_spend' => $this->total_spend,
        ];
    }
}
