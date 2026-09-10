<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->route('customer'))],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'wholesale_status' => ['sometimes', 'required', Rule::in(['retail', 'pending', 'approved', 'rejected'])],
            'business_name' => ['nullable', 'string', 'max:255', 'required_if:wholesale_status,approved'],
        ];
    }
}
