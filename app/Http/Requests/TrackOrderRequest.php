<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class TrackOrderRequest extends FormRequest
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
        return [
            'order_number' => ['required', 'string', 'max:30', 'regex:/^ORD\\d+$/i'],
            'customer_phone' => ['required', 'string', 'max:30'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'order_number' => strtoupper(trim((string) $this->input('order_number'))),
            'customer_phone' => trim((string) $this->input('customer_phone')),
        ]);
    }

    public function messages(): array
    {
        return [
            'order_number.regex' => 'Enter a valid order number, for example ORD00008.',
        ];
    }
}
