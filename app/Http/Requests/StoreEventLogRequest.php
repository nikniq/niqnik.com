<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEventLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'max:64'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'source' => ['nullable', 'string', 'max:128'],
            'occurred_at' => ['nullable', 'date'],
            'context' => ['nullable', 'array'],
        ];
    }

    public function context(): array
    {
        $context = $this->validated('context', []);

        if ($this->filled('source')) {
            $context['_source'] = $this->validated('source');
        }

        if ($this->filled('occurred_at')) {
            $context['_occurred_at'] = $this->validated('occurred_at');
        }

        $context['_ip'] = $this->ip();

        return $context;
    }
}
