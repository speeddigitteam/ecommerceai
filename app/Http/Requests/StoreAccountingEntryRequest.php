<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountingEntryRequest extends FormRequest
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
            'entry_date' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.category_id' => ['required', 'integer', 'distinct', Rule::exists($this->routeIs('expenses.*') ? 'expense_categories' : 'income_categories', 'id')],
            'items.*.amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
        ];
    }
}
