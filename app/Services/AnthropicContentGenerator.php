<?php

namespace App\Services;

use App\Models\WebsiteSetting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AnthropicContentGenerator
{
    /** @param array{content_type:string,topic:string,keywords?:string|null,context?:string|null,language:string,tone:string,length:string} $input */
    public function generate(array $input): array
    {
        $settings = WebsiteSetting::query()->first();
        if (! $settings?->anthropic_enabled || blank($settings->anthropic_api_key)) {
            throw ValidationException::withMessages(['ai' => 'Configure and activate Claude from AI API settings first.']);
        }

        try {
            $response = Http::baseUrl('https://api.anthropic.com/v1')
                ->withHeaders([
                    'x-api-key' => $settings->anthropic_api_key,
                    'anthropic-version' => '2023-06-01',
                ])
                ->acceptJson()
                ->timeout(60)
                ->retry(2, 500, throw: false)
                ->post('/messages', [
                    'model' => $settings->anthropic_model ?: 'claude-sonnet-4-5',
                    'max_tokens' => $settings->anthropic_max_output_tokens ?: 2500,
                    'system' => 'Write accurate ecommerce content for Bangladesh. Never invent facts. Return only valid JSON with title, description, excerpt, seo_title, meta_description, and tags. Description must use clean HTML. SEO title must be at most 60 characters and meta description at most 160 characters.',
                    'messages' => [[
                        'role' => 'user',
                        'content' => $this->prompt($input),
                    ]],
                ]);
        } catch (ConnectionException) {
            throw ValidationException::withMessages(['ai' => 'Claude could not be reached. Please try again.']);
        }

        if ($response->failed()) {
            throw ValidationException::withMessages(['ai' => $response->json('error.message', 'Claude request failed.')]);
        }

        $text = collect($response->json('content', []))->firstWhere('type', 'text')['text'] ?? null;
        $content = is_string($text) ? $this->decodeJson($text) : null;
        if (! is_array($content) || collect(['title', 'description', 'excerpt', 'seo_title', 'meta_description', 'tags'])->contains(fn (string $field): bool => ! array_key_exists($field, $content))) {
            throw ValidationException::withMessages(['ai' => 'Claude returned an invalid response.']);
        }

        return $content;
    }

    /** @param array{content_type:string,topic:string,keywords?:string|null,context?:string|null,language:string,tone:string,length:string} $input */
    private function prompt(array $input): string
    {
        return "Type: {$input['content_type']}\nTopic: {$input['topic']}\nLanguage: {$input['language']}\nTone: {$input['tone']}\nLength: {$input['length']}\nKeywords: ".($input['keywords'] ?? '')."\nFacts/context: ".($input['context'] ?? '');
    }

    /** @return array<string, mixed>|null */
    private function decodeJson(string $text): ?array
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start === false || $end === false || $end < $start) {
            return null;
        }

        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);

        return is_array($decoded) ? $decoded : null;
    }
}
