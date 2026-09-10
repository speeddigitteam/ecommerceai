<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateAiContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'content_type' => ['required', 'in:product,blog'], 'topic' => ['required', 'string', 'max:300'], 'keywords' => ['nullable', 'string', 'max:500'],
            'context' => ['nullable', 'string', 'max:3000'], 'language' => ['required', 'in:Bangla,English,Bangla and English'],
            'tone' => ['required', 'in:Professional,Friendly,Persuasive,Luxury,Simple'], 'length' => ['required', 'in:short,medium,long'],
        ];
    }
}
