<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAiProviderRequest;
use App\Models\WebsiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AiApiSettingController extends Controller
{
    public function index(): View
    {
        $settings = $this->settings();

        return view('settings.ai-apis', [
            'settings' => $settings,
            'providers' => [
                [
                    'id' => 'openai',
                    'name' => 'OpenAI',
                    'description' => 'Generate product and blog content with OpenAI.',
                    'enabled' => (bool) $settings->openai_enabled,
                    'key_configured' => filled($settings->getRawOriginal('openai_api_key')),
                    'model' => $settings->openai_model ?: 'gpt-5.2',
                    'max_output_tokens' => $settings->openai_max_output_tokens ?: 2500,
                    'key_placeholder' => 'sk-...',
                ],
                [
                    'id' => 'anthropic',
                    'name' => 'Claude',
                    'description' => 'Generate product and blog content with Anthropic Claude.',
                    'enabled' => (bool) $settings->anthropic_enabled,
                    'key_configured' => filled($settings->getRawOriginal('anthropic_api_key')),
                    'model' => $settings->anthropic_model ?: 'claude-sonnet-4-5',
                    'max_output_tokens' => $settings->anthropic_max_output_tokens ?: 2500,
                    'key_placeholder' => 'sk-ant-...',
                ],
            ],
        ]);
    }

    public function update(UpdateAiProviderRequest $request, string $provider): RedirectResponse
    {
        $this->ensureProviderExists($provider);
        $settings = $this->settings();
        $validated = $request->validated();
        $attributes = [
            $provider.'_model' => $validated['model'],
            $provider.'_max_output_tokens' => $validated['max_output_tokens'],
            'openai_default_language' => $validated['default_language'],
            'openai_default_tone' => $validated['default_tone'],
        ];

        if ($request->filled('api_key')) {
            $attributes[$provider.'_api_key'] = $request->string('api_key')->toString();
        }

        $settings->update($attributes);

        return to_route('settings.ai-apis.index')->with('status', ucfirst($provider).' API settings updated successfully.');
    }

    public function toggle(Request $request, string $provider): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $this->ensureProviderExists($provider);
        $validated = $request->validate(['active' => ['required', 'boolean']]);
        $settings = $this->settings();

        if ($validated['active']) {
            $hasApiKey = filled($settings->getRawOriginal($provider.'_api_key'));
            if (! $hasApiKey) {
                throw ValidationException::withMessages([
                    'provider' => ucfirst($provider).' cannot be activated until its API key is configured.',
                ]);
            }

            $settings->update([
                'openai_enabled' => $provider === 'openai',
                'anthropic_enabled' => $provider === 'anthropic',
            ]);
        } else {
            $settings->update([$provider.'_enabled' => false]);
        }

        return to_route('settings.ai-apis.index')->with('status', ucfirst($provider).' API '.($validated['active'] ? 'activated' : 'deactivated').' successfully.');
    }

    private function ensureProviderExists(string $provider): void
    {
        abort_unless(in_array($provider, ['openai', 'anthropic'], true), 404);
    }

    private function settings(): WebsiteSetting
    {
        return WebsiteSetting::query()->firstOrCreate([], [
            'site_name' => config('app.name', 'Shopwise'),
            'seo_title' => config('app.name', 'Shopwise'),
        ]);
    }
}
