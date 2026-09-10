<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAiContentSettingRequest;
use App\Models\WebsiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AiContentSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.ai-content', ['settings' => $this->settings()]);
    }

    public function update(UpdateAiContentSettingRequest $request): RedirectResponse
    {
        $settings = $this->settings();
        $validated = $request->safe()->except('openai_api_key');
        if ($request->filled('openai_api_key')) {
            $validated['openai_api_key'] = $request->string('openai_api_key')->toString();
        }
        $settings->update($validated);

        return to_route('settings.ai-content.edit')->with('status', 'AI content settings updated successfully.');
    }

    private function settings(): WebsiteSetting
    {
        return WebsiteSetting::query()->firstOrCreate([], ['site_name' => config('app.name', 'Shopwise'), 'seo_title' => config('app.name', 'Shopwise')]);
    }
}
