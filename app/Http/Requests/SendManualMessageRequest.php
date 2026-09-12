<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendManualMessageRequest extends FormRequest
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
            'group' => ['required', 'in:none,customers,admins,newsletter'],
            'recipients' => ['nullable', 'string', 'max:10000'],
            'subject' => ['required_if:channel,email', 'nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:50000'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }
}
