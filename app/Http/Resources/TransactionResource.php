<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
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
            'account_id' => $this->account_id,
            'account_name' => $this->account?->name,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'spender_user_id' => $this->spender_user_id,
            'spender_name' => $this->spender?->name,
            'amount' => (float) $this->amount,
            'type' => $this->type,
            'description' => $this->description,
            'transaction_at' => $this->transaction_at,
            'to_account_id' => $this->to_account_id,
            'to_account_name' => $this->toAccount?->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
