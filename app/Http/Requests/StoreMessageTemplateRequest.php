<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageTemplateRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['channel' => $this->route('channel')]);
    }

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'channel' => ['required', 'in:email,sms'],
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['required_if:channel,email', 'nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:50000'],
            'category' => ['nullable', 'in:transactional,marketing,automation'],
            'preview_text' => ['nullable', 'string', 'max:255'],
            'button_text' => ['nullable', 'string', 'max:100'],
            'button_url' => ['nullable', 'string', 'max:1000'],
            'is_important' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
