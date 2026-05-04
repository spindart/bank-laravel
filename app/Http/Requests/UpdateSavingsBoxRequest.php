<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSavingsBoxRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:80'],
            'description' => ['nullable', 'string', 'max:500'],
            'target_amount' => ['required', 'decimal:0,2', 'gt:0'],
            'target_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'icon' => ['nullable', 'string', 'max:40'],
        ];
    }
}
