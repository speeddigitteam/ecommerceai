<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHeroSettingRequest extends FormRequest
{
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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'hero_title' => ['nullable', 'string', 'max:100'],
            'hero_subtitle' => ['nullable', 'string', 'max:160'],
            'hero_product_id' => ['nullable', Rule::exists('products', 'id')->where(fn ($query) => $query->where('status', 'published')->where('visibility', 'public')->whereNotNull('price'))],
            'slider_images' => ['nullable', 'array', 'max:8'],
            'slider_images.*' => ['image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'remove_slider_images' => ['nullable', 'array'],
            'remove_slider_images.*' => ['string'],
            'hero_side_image_one' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'hero_side_image_two' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'remove_hero_side_image_one' => ['nullable', 'boolean'],
            'remove_hero_side_image_two' => ['nullable', 'boolean'],
        ];
    }
}
