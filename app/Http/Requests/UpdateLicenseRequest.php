<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'seats_total' => ['required', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'domains' => ['nullable', 'string'],
        ];
    }
}
