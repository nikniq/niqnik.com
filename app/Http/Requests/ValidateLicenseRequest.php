<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateLicenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'license_code' => ['required', 'string'],
            'seats_requested' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function seatsRequested(): int
    {
        return (int) ($this->input('seats_requested', 1));
    }
}
