<?php

namespace App\Http\Resources;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Account
 */
class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->user?->name,
            'name' => $this->name,
            'type' => $this->type,
            'initial_balance' => (float) $this->initial_balance,
            'currency' => $this->currency,
            'is_closed' => (bool) $this->is_closed,
            'close_at' => $this->close_at,
            'balance' => (float) $this->balance,
            'bank' => $this->bank,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
