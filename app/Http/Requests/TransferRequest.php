<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'receiver_user_id' => [
                'required',
                'integer',
                'exists:users,id',
                Rule::notIn([$this->user()?->id]),
            ],
            'amount' => ['required', 'decimal:0,2', 'gt:0'],
            'idempotency_key' => ['nullable', 'string', 'max:128'],
        ];
    }
}
