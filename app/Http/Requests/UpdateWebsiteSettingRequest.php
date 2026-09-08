<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebsiteSettingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $keywords = array_filter(array_map('trim', explode(',', (string) $this->input('meta_keywords'))));

        $this->merge([
            'meta_keywords' => $keywords === [] ? null : implode(', ', array_unique($keywords)),
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:100'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'business_address' => ['nullable', 'string', 'max:500'],
            'website_url' => ['nullable', 'url:http,https', 'max:255'],
            'seo_title' => ['required', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_keywords' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:png,ico,jpg,jpeg,webp', 'max:1024'],
            'featured_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ];
    }
}
