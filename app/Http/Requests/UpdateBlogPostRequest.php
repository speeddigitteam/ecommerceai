<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateBlogPostRequest extends StoreBlogPostRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['slug'] = ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('blog_posts', 'slug')->ignore($this->route('blogPost'))];

        return $rules;
    }
}
