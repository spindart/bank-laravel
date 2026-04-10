<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['credit', 'debit'])],
            'amount' => ['required', 'decimal:0,2', 'gt:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['nullable', 'date'],
        ];
    }
}
