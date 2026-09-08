<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFlashSaleSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'include_all_sale_products' => ['required', 'boolean'],
            'product_ids' => ['required_if:include_all_sale_products,0', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
            'eyebrow' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['required', 'string', 'max:500'],
            'ends_at' => ['nullable', 'date'],
            'button_text' => ['required', 'string', 'max:40'],
            'background_from' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'background_via' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'background_to' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
