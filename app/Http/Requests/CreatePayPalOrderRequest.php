<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePayPalOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'exists:products,id'],
            'seats_total' => ['required', 'integer', 'min:1', 'max:1'],
            'domain' => ['nullable', 'string', 'max:255'],
        ];
    }
}
