<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceMarqueeSettingRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'size:5'],
            'items.*.label' => ['required', 'string', 'max:160'],
            'items.*.icon' => ['required', 'in:price,delivery,quality,payment,support'],
            'speed' => ['required', 'integer', 'min:10', 'max:120'],
            'enabled' => ['nullable', 'boolean'],
        ];
    }
}
