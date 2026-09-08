<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateFaqSettingRequest;
use App\Models\WebsiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FaqSettingController extends Controller
{
    public function edit(): View
    {
        $settings = $this->settings();

        return view('settings.faq', [
            'settings' => $settings,
            'faqs' => $settings->homepage_faqs ?? WebsiteSetting::defaultHomepageFaqs(),
        ]);
    }

    public function update(UpdateFaqSettingRequest $request): RedirectResponse
    {
        $faqs = collect($request->validated('faqs', []))
            ->map(fn (array $faq): array => [
                'question' => str($faq['question'])->squish()->toString(),
                'answer' => str($faq['answer'])->squish()->toString(),
            ])
            ->values()
            ->all();

        $this->settings()->update(['homepage_faqs' => $faqs]);

        return to_route('settings.faq.edit')->with('status', 'Homepage FAQs updated successfully.');
    }

    private function settings(): WebsiteSetting
    {
        return WebsiteSetting::query()->firstOrCreate([], [
            'site_name' => config('app.name', 'Shopwise'),
            'seo_title' => config('app.name', 'Shopwise'),
        ]);
    }
}
