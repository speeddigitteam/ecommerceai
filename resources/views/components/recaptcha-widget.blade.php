@php($recaptchaSettings = App\Models\WebsiteSetting::query()->first())
@if ($recaptchaSettings?->recaptcha_enabled && filled($recaptchaSettings->recaptcha_site_key))
    @once
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endonce
    <div class="overflow-x-auto pb-1">
        <div class="g-recaptcha" data-sitekey="{{ $recaptchaSettings->recaptcha_site_key }}"></div>
    </div>
    @error('g-recaptcha-response')<p class="mt-2 text-xs font-medium text-rose-600 dark:text-rose-400">{{ $message }}</p>@enderror
@endif