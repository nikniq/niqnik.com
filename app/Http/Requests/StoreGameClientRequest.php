<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGameClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    protected function prepareForValidation(): void
    {
        $uris = $this->input('redirect_uris');
        if (is_string($uris)) {
            $uris = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $uris))));
        }
        $this->merge([
            'redirect_uris' => $uris,
            'slug' => $this->input('slug') ? str($this->input('slug'))->slug()->toString() : null,
            'is_public' => $this->boolean('is_public'),
            'is_enabled' => $this->boolean('is_enabled'),
        ]);
    }

    public function rules(): array
    {
        $id = $this->route('game_client')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:64', Rule::unique('game_clients', 'slug')->ignore($id)],
            'product_id' => ['nullable', 'exists:products,id', Rule::unique('game_clients', 'product_id')->ignore($id)],
            'homepage_url' => ['nullable', 'url', 'max:2048'],
            'redirect_uris' => ['required', 'array', 'min:1', 'max:20'],
            'redirect_uris.*' => ['required', 'url', 'max:2048'],
            'is_public' => ['boolean'],
            'is_enabled' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'redirect_uris.*.url' => 'Each redirect URI must be a full URL, one per line.',
        ];
    }
}
