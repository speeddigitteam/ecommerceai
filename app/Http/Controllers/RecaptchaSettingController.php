<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRecaptchaSettingRequest;
use App\Models\WebsiteSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RecaptchaSettingController extends Controller
{
    public function edit(): View
    {
        return view('settings.recaptcha', ['settings' => $this->settings()]);
    }

    public function update(UpdateRecaptchaSettingRequest $request): RedirectResponse
    {
        $settings = $this->settings();
        $enabled = $request->boolean('enabled');
        $siteKey = $request->string('site_key')->trim()->toString();
        $secretKey = $request->string('secret_key')->trim()->toString();

        $configurationErrors = [];

        if ($enabled && $siteKey === '') {
            $configurationErrors['site_key'] = 'The Site Key is required when reCAPTCHA is enabled.';
        }

        if ($enabled && $secretKey === '' && blank($settings->recaptcha_secret_key)) {
            $configurationErrors['secret_key'] = 'The Secret Key is required when reCAPTCHA is enabled.';
        }

        if ($configurationErrors !== []) {
            throw ValidationException::withMessages($configurationErrors);
        }

        $settings->recaptcha_enabled = $enabled;
        $settings->recaptcha_site_key = $siteKey !== '' ? $siteKey : null;

        if ($secretKey !== '') {
            $settings->recaptcha_secret_key = $secretKey;
        }

        $settings->save();

        return to_route('settings.recaptcha.edit')->with('status', 'reCAPTCHA settings updated successfully.');
    }

    private function settings(): WebsiteSetting
    {
        return WebsiteSetting::query()->firstOrCreate([], [
            'site_name' => config('app.name', 'Shopwise'),
            'seo_title' => config('app.name', 'Shopwise'),
        ]);
    }
}
