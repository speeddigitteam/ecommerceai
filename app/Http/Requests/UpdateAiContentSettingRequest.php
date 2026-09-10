<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAiContentSettingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['openai_enabled' => $this->boolean('openai_enabled')]);
    }

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'openai_enabled' => ['required', 'boolean'], 'openai_api_key' => ['nullable', 'string', 'starts_with:sk-', 'max:500'],
            'openai_model' => ['required', 'string', 'regex:/^[a-zA-Z0-9._-]+$/', 'max:100'], 'openai_max_output_tokens' => ['required', 'integer', 'between:200,10000'],
            'openai_default_language' => ['required', 'in:Bangla,English,Bangla and English'], 'openai_default_tone' => ['required', 'in:Professional,Friendly,Persuasive,Luxury,Simple'],
        ];
    }
}
