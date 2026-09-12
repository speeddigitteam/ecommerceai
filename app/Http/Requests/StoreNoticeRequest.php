<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'details' => ['required', 'string', 'max:50000'],
            'publish_to' => ['required', 'array', 'min:1'],
            'publish_to.*' => ['string', 'in:dashboard,customers,storefront'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
