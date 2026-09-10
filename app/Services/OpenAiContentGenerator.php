<?php

namespace App\Services;

use App\Models\WebsiteSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class OpenAiContentGenerator
{
    /** @param array{content_type:string,topic:string,keywords?:string|null,context?:string|null,language:string,tone:string,length:string} $input */
    public function generate(array $input): array
    {
        $settings = WebsiteSetting::query()->first();
        if (! $settings?->openai_enabled || blank($settings->openai_api_key)) {
            throw ValidationException::withMessages(['openai' => 'Configure and enable OpenAI from AI Content settings first.']);
        }
        try {
            $response = Http::baseUrl('https://api.openai.com/v1')->withToken($settings->openai_api_key)->acceptJson()->timeout(60)->retry(2, 500, throw: false)->post('/responses', [
                'model' => $settings->openai_model ?: 'gpt-5.2', 'store' => false, 'max_output_tokens' => $settings->openai_max_output_tokens ?: 2500,
                'instructions' => 'Write accurate ecommerce content for Bangladesh. Never invent facts. Description must use clean HTML. Return every requested field.',
                'input' => $this->prompt($input), 'text' => ['format' => $this->format()],
            ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages(['openai' => 'OpenAI could not be reached. Please try again.']);
        }
        if ($response->failed()) {
            throw ValidationException::withMessages(['openai' => $response->json('error.message', 'OpenAI request failed.')]);
        }
        $text = collect($response->json('output', []))->flatMap(fn (array $item): array => $item['content'] ?? [])->firstWhere('type', 'output_text')['text'] ?? null;
        $content = is_string($text) ? json_decode($text, true) : null;
        if (! is_array($content)) {
            throw ValidationException::withMessages(['openai' => 'OpenAI returned an invalid response.']);
        }

        return $content;
    }

    private function prompt(array $input): string
    {
        return "Type: {$input['content_type']}\nTopic: {$input['topic']}\nLanguage: {$input['language']}\nTone: {$input['tone']}\nLength: {$input['length']}\nKeywords: ".($input['keywords'] ?? '')."\nFacts/context: ".($input['context'] ?? '')."\nWrite title, HTML description, excerpt, SEO title (max 60), meta description (max 160), and tags.";
    }

    private function format(): array
    {
        $properties = ['title' => ['type' => 'string'], 'description' => ['type' => 'string'], 'excerpt' => ['type' => 'string'], 'seo_title' => ['type' => 'string'], 'meta_description' => ['type' => 'string'], 'tags' => ['type' => 'array', 'items' => ['type' => 'string']]];

        return ['type' => 'json_schema', 'name' => 'ecommerce_content', 'strict' => true, 'schema' => ['type' => 'object', 'properties' => $properties, 'required' => array_keys($properties), 'additionalProperties' => false]];
    }
}
