<?php

namespace App\Http\Requests;

use App\Services\DeliveryCharges;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDeliveryChargesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $rules = ['delivery_charges' => ['required', 'array:dhaka_city,dhaka_outside,outside_dhaka']];
        foreach (DeliveryCharges::AREAS as $area => $label) {
            $rules['delivery_charges.'.$area] = ['required', 'numeric', 'min:0', 'max:999999', 'decimal:0,2'];
        }

        return $rules;
    }
}
