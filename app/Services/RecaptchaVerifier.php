<?php

namespace App\Services;

use App\Models\WebsiteSetting;
use Illuminate\Support\Facades\Http;

class RecaptchaVerifier
{
    public function verify(?string $token, ?string $remoteIp): bool
    {
        $settings = WebsiteSetting::query()->first();

        if (! $settings?->recaptcha_enabled) {
            return true;
        }

        if (blank($settings->recaptcha_site_key) || blank($settings->recaptcha_secret_key) || blank($token)) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->timeout(5)
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => $settings->recaptcha_secret_key,
                    'response' => $token,
                    'remoteip' => $remoteIp,
                ]);

            return $response->successful() && $response->json('success') === true;
        } catch (\Throwable) {
            return false;
        }
    }
}
