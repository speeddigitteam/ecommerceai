<?php

namespace App\Http\Requests;

use App\Http\Controllers\CartController;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $needsShipping = $this->hasPhysicalItem();

        return [
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:150'],
            'shipping_address' => [$needsShipping ? 'required' : 'nullable', 'string', 'max:1000'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'delivery_area' => [$needsShipping ? 'required' : 'nullable', 'in:dhaka_city,dhaka_outside,outside_dhaka'],
            'payment_method' => ['required', 'in:cash_on_delivery'],
        ];
    }

    protected function hasPhysicalItem(): bool
    {
        return app(CartController::class)->cartData($this)['items']->contains(fn (array $item): bool => ! $item['product']->isDigital());
    }
}
