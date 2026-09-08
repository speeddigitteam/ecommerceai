<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourierIntegrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
        return [
            'provider' => ['required', Rule::in(['steadfast', 'pathao', 'redx', 'paperfly'])],
            'name' => ['required', 'string', 'max:100'],
            'api_key' => ['required', 'string', 'max:1000'],
            'secret_key' => ['required', 'string', 'max:1000'],
            'base_url' => ['required', 'url:http,https', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
