<?php

namespace App\Http\Requests;

use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class TransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric'],
            'type' => ['required', 'string', new Enum(TransactionType::class)],
            'category_id' => ['required', 'exists:categories,id'],
            'account_id' => ['required', 'exists:accounts,id'],
            'spender_user_id' => ['required', 'exists:users,id'],
            'transaction_at' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'to_account_id' => ['required_if:type,'.TransactionType::Transfer->value, 'nullable', 'exists:accounts,id'],
        ];
    }
}
