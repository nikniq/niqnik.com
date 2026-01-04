<?php

namespace App\Http\Requests;

use App\Models\Server;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'hostname' => [
                'required',
                'string',
                'max:255',
                Rule::unique('servers', 'hostname')->ignore($this->route('server')->id ?? null),
            ],
            'status' => ['required', 'string', Rule::in(Server::STATUSES)],
            'environment' => ['nullable', 'string', 'max:64'],
            'last_seen_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
