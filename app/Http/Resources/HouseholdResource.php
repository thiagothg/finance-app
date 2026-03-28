<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\HouseholdMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $name
 * @property int $owner_id
 * @property HouseholdMember|null $currentUserMembership
 */
class HouseholdResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'owner_id' => $this->owner_id,
            'invitation_code' => $this->invitation_code,
            'current_user_membership' => $this->when(
                $this->relationLoaded('currentUserMembership') && $this->currentUserMembership !== null,
                fn (): array => [
                    'user_id' => $this->currentUserMembership->user_id,
                    'role' => $this->currentUserMembership->role,
                    'status' => $this->currentUserMembership->status,
                ]
            ),
            'members_count' => $this->whenCounted('members'),
            'members' => HouseholdMemberResource::collection($this->whenLoaded('members')),
        ];
    }
}
