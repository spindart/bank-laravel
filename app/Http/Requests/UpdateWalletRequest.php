<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'balance' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}

