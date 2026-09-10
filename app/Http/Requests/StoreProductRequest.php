<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
        $rules = [
            'delivery_charge_type' => ['sometimes', 'required', Rule::in(['default', 'free', 'custom'])],
            'delivery_charges' => ['nullable', 'array:dhaka_city,dhaka_outside,outside_dhaka'],
            'delivery_charges.dhaka_city' => ['required_if:delivery_charge_type,custom', 'nullable', 'numeric', 'min:0', 'max:999999', 'decimal:0,2'],
            'delivery_charges.dhaka_outside' => ['required_if:delivery_charge_type,custom', 'nullable', 'numeric', 'min:0', 'max:999999', 'decimal:0,2'],
            'delivery_charges.outside_dhaka' => ['required_if:delivery_charge_type,custom', 'nullable', 'numeric', 'min:0', 'max:999999', 'decimal:0,2'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('products', 'slug')],
            'description' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'specifications' => ['nullable', 'array'],
            'specifications.*.title' => ['required', 'string', 'max:255'],
            'specifications.*.items' => ['required', 'array', 'min:1'],
            'specifications.*.items.*.title' => ['required', 'string', 'max:255'],
            'specifications.*.items.*.value' => ['required', 'string', 'max:1000'],
            'questions' => ['nullable', 'array'],
            'questions.*.question' => ['required', 'string', 'max:500'],
            'questions.*.answer' => ['required', 'string', 'max:2000'],
            'type' => ['required', Rule::in(['physical', 'digital'])],
            'digital_file' => ['nullable', 'file', 'max:102400'],
            'remove_digital_file' => ['nullable', 'boolean'],
            'wholesale_tiers' => ['nullable', 'array'],
            'wholesale_tiers.*.minimum_quantity' => ['nullable', 'integer', 'min:2', 'distinct'],
            'wholesale_tiers.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.sale_price' => ['nullable', 'numeric', 'min:0', 'lte:variants.*.price'],
            'variants.*.stock_quantity' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'variants.*.remove_image' => ['nullable', 'boolean'],
            'variants.*.options' => ['required_with:variants', 'array', 'min:1'],
            'variants.*.options.*.name' => ['required', 'string', 'max:100'],
            'variants.*.options.*.value' => ['required', 'string', 'max:100'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')],
            'price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'stock_quantity' => ['required_if:type,physical', 'nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'visibility' => ['required', Rule::in(['public', 'private'])],
            'published_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'string', 'max:1000'],
            'focus_keyword' => ['nullable', 'string', 'max:100'],
            'seo_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'featured_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'gallery' => ['nullable', 'array', 'max:12'],
            'gallery.*' => ['image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
            'video' => ['nullable', 'file', 'mimes:mp4,webm,mov', 'max:20480'],
        ];

        foreach ($this->input('variants', []) as $index => $variant) {
            $rules["variants.{$index}.sku"] = [
                'nullable', 'string', 'max:100',
                Rule::unique('product_variants', 'sku')->ignore($variant['id'] ?? null),
            ];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('type') === 'digital') {
                $product = $this->route('product');
                $keepsExistingFile = $product?->digital_file_path && ! $this->boolean('remove_digital_file');
                if (! $this->hasFile('digital_file') && ! $keepsExistingFile) {
                    $validator->errors()->add('digital_file', 'Please upload a file for this digital product.');
                }
            }

            $variants = collect($this->input('variants', []));

            $duplicateSkus = $variants->pluck('sku')->filter()->map(fn (string $sku): string => strtolower(trim($sku)))->duplicates();
            if ($duplicateSkus->isNotEmpty()) {
                $validator->errors()->add('variants', 'Variant SKUs must be unique.');
            }

            $duplicateOptionSets = $variants->map(fn (array $variant): string => collect($variant['options'] ?? [])
                ->map(fn (array $option): string => strtolower(trim($option['name'] ?? '')).':'.strtolower(trim($option['value'] ?? '')))
                ->sort()->implode('|'))->duplicates();
            if ($duplicateOptionSets->isNotEmpty()) {
                $validator->errors()->add('variants', 'Each variant must have a unique combination of options.');
            }
        });
    }
}
