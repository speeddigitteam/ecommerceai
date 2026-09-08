<x-admin-layout title="reCAPTCHA Settings">
    <div class="ds-page">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />

        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />
            <div class="mx-auto max-w-4xl p-5 sm:p-8">
                <div class="mb-6">
                    <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Website Settings</p>
                    <h1 class="mt-1 text-2xl font-bold">Google reCAPTCHA</h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Protect both customer and admin login forms with the reCAPTCHA v2 checkbox.</p>
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ session('status') }}</div>
                @endif

                <form data-ds-editable data-ds-editable-start="edit" method="POST" action="{{ route('settings.recaptcha.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <section class="ds-card ds-card-body">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="ds-section-title">Login protection</h2>
                                <p class="ds-section-description">Enable this only after adding valid Google reCAPTCHA v2 keys for your domain.</p>
                            </div>
                            <label class="flex items-center gap-2 text-sm font-semibold">
                                <input type="hidden" name="enabled" value="0">
                                <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings->recaptcha_enabled)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                Enabled
                            </label>
                        </div>

                        <div class="mt-6 space-y-5">
                            <div>
                                <label for="recaptcha-site-key" class="ds-field-label">Site Key</label>
                                <input id="recaptcha-site-key" name="site_key" value="{{ old('site_key', $settings->recaptcha_site_key) }}" autocomplete="off" class="ds-input ds-control font-mono" placeholder="Paste your reCAPTCHA v2 Site Key">
                                @error('site_key')<p class="ds-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="recaptcha-secret-key" class="ds-field-label">Secret Key</label>
                                <input id="recaptcha-secret-key" name="secret_key" type="password" autocomplete="new-password" class="ds-input ds-control font-mono" placeholder="{{ filled($settings->recaptcha_secret_key) ? 'Configured — leave blank to keep it' : 'Paste your reCAPTCHA v2 Secret Key' }}">
                                <p class="ds-help">The Secret Key is encrypted before being stored and is never shown again.</p>
                                @error('secret_key')<p class="ds-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="flex justify-end">
                        <button class="ds-button-primary px-5">Save reCAPTCHA settings</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</x-admin-layout>