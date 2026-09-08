<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateWebsiteContentRequest;
use App\Models\WebsiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WebsiteContentController extends Controller
{
    public function edit(): View
    {
        return view('settings.content', ['settings' => $this->settings()]);
    }

    public function update(UpdateWebsiteContentRequest $request): RedirectResponse
    {
        $content = strip_tags((string) $request->validated('footer_content'), '<p><br><h1><h2><h3><h4><strong><b><em><i><u><ul><ol><li><blockquote><a>');
        $content = preg_replace('/\s+on\w+=(["\']).*?\1/is', '', $content) ?? '';
        $content = preg_replace('/href=(["\'])\s*javascript:.*?\1/is', 'href="#"', $content) ?? '';
        $this->settings()->update(['footer_content' => trim($content) ?: null]);

        return to_route('settings.content.edit')->with('status', 'Website content updated successfully.');
    }

    private function settings(): WebsiteSetting
    {
        return WebsiteSetting::query()->firstOrCreate([], ['site_name' => config('app.name', 'Shopwise'), 'seo_title' => config('app.name', 'Shopwise')]);
    }
}
