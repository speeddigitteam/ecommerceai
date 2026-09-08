<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnalyticsSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'tracking_enabled' => ['required', 'boolean'],
            'measurement_id' => ['nullable', 'string', 'max:50', 'regex:/^G-[A-Z0-9]+$/'],
            'property_id' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]+$/'],
            'service_account_json' => ['nullable', 'string', 'max:20000'],
            'search_console_verification' => ['nullable', 'string', 'max:255'],
        ];
    }
}
