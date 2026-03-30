<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['sometimes', Rule::in(['credit', 'debit'])],
            'amount' => ['sometimes', 'numeric', 'gt:0'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'transaction_date' => ['sometimes', 'date'],
        ];
    }
}

