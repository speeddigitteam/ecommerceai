<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $rules = parent::rules();
        $product = $this->route('product');
        $rules['slug'] = ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('products', 'slug')->ignore($product)];
        $rules['sku'] = ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product)];

        unset($rules['variants.*.sku']);
        foreach ($this->input('variants', []) as $index => $variant) {
            $rules["variants.{$index}.sku"] = ['nullable', 'string', 'max:100', Rule::unique('product_variants', 'sku')->ignore($variant['id'] ?? null)];
        }

        return $rules;
    }
}
