<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateFlashSaleSettingRequest;
use App\Models\Product;
use App\Models\WebsiteSetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FlashSaleSettingController extends Controller
{
    public function edit(): View
    {
        $settings = $this->settings();

        return view('settings.flash-sale', [
            'settings' => $settings,
            'flashSale' => array_merge(WebsiteSetting::defaultFlashSaleSettings(), $settings->flash_sale_settings ?? []),
            'saleProducts' => $this->eligibleSaleProducts()->get(),
        ]);
    }

    public function update(UpdateFlashSaleSettingRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['enabled'] = $request->boolean('enabled');
        $validated['include_all_sale_products'] = $request->boolean('include_all_sale_products');
        $validated['product_ids'] = $this->eligibleSaleProducts()
            ->whereKey($request->input('product_ids', []))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $validated['ends_at'] = filled($validated['ends_at'] ?? null)
            ? Carbon::parse($validated['ends_at'], config('app.timezone'))->toIso8601String()
            : null;

        $this->settings()->update(['flash_sale_settings' => $validated]);

        return to_route('settings.flash-sale.edit')->with('status', 'Flash Sale settings updated successfully.');
    }

    private function eligibleSaleProducts(): Builder
    {
        return Product::query()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'price')
            ->orderBy('title');
    }

    private function settings(): WebsiteSetting
    {
        return WebsiteSetting::query()->firstOrCreate([], [
            'site_name' => config('app.name', 'Shopwise'),
            'seo_title' => config('app.name', 'Shopwise'),
        ]);
    }
}
