<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAnalyticsSettingRequest;
use App\Models\WebsiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AnalyticsSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.analytics', ['settings' => $this->settings()]);
    }

    public function update(UpdateAnalyticsSettingRequest $request): RedirectResponse
    {
        $settings = $this->settings();
        $serviceAccountJson = $request->string('service_account_json')->trim()->toString();

        $configurationErrors = [];

        if ($serviceAccountJson !== '') {
            $credentials = json_decode($serviceAccountJson, true);

            if (! is_array($credentials) || blank($credentials['client_email'] ?? null) || blank($credentials['private_key'] ?? null)) {
                $configurationErrors['service_account_json'] = 'This does not look like a valid Google service account JSON key (missing client_email or private_key).';
            }
        }

        if ($configurationErrors !== []) {
            throw ValidationException::withMessages($configurationErrors);
        }

        $settings->ga_tracking_enabled = $request->boolean('tracking_enabled');
        $settings->ga_measurement_id = $request->string('measurement_id')->trim()->toString() ?: null;
        $settings->ga_property_id = $request->string('property_id')->trim()->toString() ?: null;
        $settings->search_console_verification = $request->string('search_console_verification')->trim()->toString() ?: null;

        if ($serviceAccountJson !== '') {
            $settings->ga_service_account_json = $serviceAccountJson;
        }

        $settings->save();

        return to_route('settings.analytics.edit')->with('status', 'Google settings updated successfully.');
    }

    private function settings(): WebsiteSetting
    {
        return WebsiteSetting::query()->firstOrCreate([], [
            'site_name' => config('app.name', 'Shopwise'),
            'seo_title' => config('app.name', 'Shopwise'),
        ]);
    }
}
