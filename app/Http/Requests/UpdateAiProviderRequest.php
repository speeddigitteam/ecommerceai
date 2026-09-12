<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAiProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $provider = $this->route('provider');

        return [
            'api_key' => [
                'nullable',
                'string',
                $provider === 'anthropic' ? 'starts_with:sk-ant-' : 'starts_with:sk-',
                'max:500',
            ],
            'model' => ['required', 'string', 'regex:/^[a-zA-Z0-9._-]+$/', 'max:100'],
            'max_output_tokens' => ['required', 'integer', 'between:200,10000'],
            'default_language' => ['required', Rule::in(['Bangla', 'English', 'Bangla and English'])],
            'default_tone' => ['required', Rule::in(['Professional', 'Friendly', 'Persuasive', 'Luxury', 'Simple'])],
        ];
    }
}
