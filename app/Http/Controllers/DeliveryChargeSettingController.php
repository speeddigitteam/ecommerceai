<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDeliveryChargesRequest;
use App\Models\WebsiteSetting;
use App\Services\DeliveryCharges;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DeliveryChargeSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.delivery-charges', [
            'rates' => WebsiteSetting::query()->value('delivery_charges') ?? DeliveryCharges::DEFAULTS,
        ]);
    }

    public function update(UpdateDeliveryChargesRequest $request): RedirectResponse
    {
        $settings = WebsiteSetting::query()->first() ?? new WebsiteSetting(['site_name' => config('app.name'), 'seo_title' => config('app.name')]);
        $settings->fill($request->validated())->save();

        return to_route('settings.delivery-charges.edit')->with('status', 'Delivery charges saved successfully.');
    }
}
