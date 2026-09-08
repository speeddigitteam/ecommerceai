<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateServiceMarqueeSettingRequest;
use App\Models\WebsiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceMarqueeSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.service-marquee', [
            'settings' => $this->settings(),
            'defaultItems' => $this->defaultItems(),
            'iconOptions' => [
                'price' => 'Price tag',
                'delivery' => 'Delivery truck',
                'quality' => 'Premium product',
                'payment' => 'Payment card',
                'support' => 'Customer support',
            ],
        ]);
    }

    public function update(UpdateServiceMarqueeSettingRequest $request): RedirectResponse
    {
        $this->settings()->update([
            'service_marquee_items' => array_values($request->validated('items')),
            'service_marquee_speed' => $request->integer('speed'),
            'service_marquee_enabled' => $request->boolean('enabled'),
        ]);

        return to_route('settings.service-marquee.edit')->with('status', 'Service marquee updated successfully.');
    }

    private function settings(): WebsiteSetting
    {
        return WebsiteSetting::query()->firstOrCreate([], [
            'site_name' => config('app.name', 'Shopwise'),
            'seo_title' => config('app.name', 'Shopwise'),
        ]);
    }

    /** @return list<array{label: string, icon: string}> */
    private function defaultItems(): array
    {
        return [
            ['label' => 'অন্যান্য দোকানের তুলনায় কম দাম', 'icon' => 'price'],
            ['label' => '১০০০ টাকা থেকে ফ্রি ডেলিভারি', 'icon' => 'delivery'],
            ['label' => 'প্রিমিয়াম পণ্য', 'icon' => 'quality'],
            ['label' => 'যেকোনো ব্যাংকের কার্ড দিয়ে নিরাপদ পেমেন্ট', 'icon' => 'payment'],
            ['label' => '২৪/৭ সাপোর্ট সবসময় আপনার পাশে', 'icon' => 'support'],
        ];
    }
}
