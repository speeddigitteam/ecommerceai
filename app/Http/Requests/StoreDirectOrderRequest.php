<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class StoreDirectOrderRequest extends StoreOrderRequest
{
    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$this->route('product')->stock_quantity],
        ];
    }

    protected function hasPhysicalItem(): bool
    {
        return ! $this->route('product')->isDigital();
    }
}
