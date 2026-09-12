<?php

namespace App\Services;

use App\Models\WebsiteSetting;
use Illuminate\Validation\ValidationException;

class AiContentGenerator
{
    public function __construct(
        private readonly OpenAiContentGenerator $openAi,
        private readonly AnthropicContentGenerator $anthropic,
    ) {}

    /** @param array{content_type:string,topic:string,keywords?:string|null,context?:string|null,language:string,tone:string,length:string} $input */
    public function generate(array $input): array
    {
        $settings = WebsiteSetting::query()->first();

        if ($settings?->anthropic_enabled) {
            return $this->anthropic->generate($input);
        }

        if ($settings?->openai_enabled) {
            return $this->openAi->generate($input);
        }

        throw ValidationException::withMessages([
            'ai' => 'Configure and activate an AI provider from AI API settings first.',
        ]);
    }
}
