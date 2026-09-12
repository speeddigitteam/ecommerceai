<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommunicationProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'driver' => ['required', 'string', Rule::in($this->route('channel') === 'email' ? ['smtp'] : ['http'])],
            'host' => ['required_if:driver,smtp', 'nullable', 'string', 'max:255'],
            'port' => ['required_if:driver,smtp', 'nullable', 'integer', 'between:1,65535'],
            'encryption' => ['nullable', Rule::in(['tls', 'ssl', 'none'])],
            'username' => ['required_if:driver,smtp', 'nullable', 'string', 'max:255'],
            'password' => [Rule::requiredIf(fn (): bool => $this->input('driver') === 'smtp' && ! $this->route('communicationProvider')), 'nullable', 'string', 'max:1000'],
            'from_address' => ['required_if:driver,smtp', 'nullable', 'email', 'max:255'],
            'from_name' => ['required_if:driver,smtp', 'nullable', 'string', 'max:255'],
            'url' => ['required_if:driver,http', 'nullable', 'url', 'starts_with:https://', 'max:1000'],
            'api_key' => ['nullable', 'string', 'max:1000'],
            'sender_id' => ['nullable', 'string', 'max:100'],
            'to_parameter' => ['required_if:driver,http', 'nullable', 'alpha_dash', 'max:100'],
            'message_parameter' => ['required_if:driver,http', 'nullable', 'alpha_dash', 'max:100'],
            'api_key_parameter' => ['nullable', 'alpha_dash', 'max:100'],
            'sender_parameter' => ['nullable', 'alpha_dash', 'max:100'],
            'message_type' => ['nullable', Rule::in(['auto', 'text', 'unicode'])],
            'label' => ['nullable', Rule::in(['transactional', 'promotional'])],
            'balance_url' => [
                'nullable',
                'string',
                'max:1000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $validationUrl = str_replace('{api_key}', 'api-key', (string) $value);
                    if (! str_starts_with($validationUrl, 'https://') || filter_var($validationUrl, FILTER_VALIDATE_URL) === false) {
                        $fail('The balance URL must be a valid HTTPS URL. You may use {api_key} as the key placeholder.');
                    }
                },
            ],
        ];
    }
}
