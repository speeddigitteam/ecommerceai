<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBackupSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'disk' => ['required', Rule::in(['local', 's3'])],
            'frequency' => ['required', Rule::in(['off', 'daily', 'weekly', 'monthly'])],
            'path_prefix' => ['nullable', 'string', 'max:100'],
            's3_key' => ['nullable', 'string', 'max:255'],
            's3_secret' => ['nullable', 'string', 'max:1000'],
            's3_region' => ['nullable', 'string', 'max:255'],
            's3_bucket' => ['nullable', 'string', 'max:255'],
            's3_endpoint' => ['nullable', 'url', 'max:255'],
            's3_use_path_style_endpoint' => ['nullable', 'boolean'],
        ];
    }
}
