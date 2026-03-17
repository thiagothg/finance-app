<?php

namespace App\Http\Requests;

use App\Enums\AccountType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class AccountRequest extends FormRequest
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
        // the target user is either explicitly provided (when a household owner creates for someone else)
        // or defaults to the authenticated user.
        $targetUserId = $this->input('user_id', $this->user()->id);

        $uniqueRule = Rule::unique('accounts')->where(function ($query) use ($targetUserId) {
            return $query->where('user_id', $targetUserId)
                ->where('bank', $this->input('bank'))
                ->where('type', $this->input('type'));
        });

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $uniqueRule->ignore($this->route('account')->id);
        }

        return [
            'name' => ['required', 'string', 'max:255', $uniqueRule],
            'type' => ['required', 'string', new Enum(AccountType::class)],
            'initial_balance' => ['required', 'numeric'],
            'currency' => ['required', 'string', 'max:3'],
            'bank' => ['required', 'string', 'max:50'],
            'user_id' => ['sometimes', 'exists:users,id'],
            'is_closed' => ['sometimes', 'boolean'],
            'close_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
