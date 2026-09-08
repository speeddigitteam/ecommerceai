<x-admin-layout title="Google Integrations">
    <div class="ds-page">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />

        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />
            <div class="mx-auto max-w-4xl p-5 sm:p-8">
                <div class="mb-6">
                    <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Website Settings</p>
                    <h1 class="mt-1 text-2xl font-bold">Google Integrations</h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Track storefront visitors, view the results from the Analytics menu, and verify your site with Google Search Console.</p>
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('settings.analytics.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <section class="ds-card ds-card-body">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="ds-section-title">Storefront tracking</h2>
                                <p class="ds-section-description">Adds the Google tag (gtag.js) to every storefront page so visits are recorded in Google Analytics.</p>
                            </div>
                            <label class="flex items-center gap-2 text-sm font-semibold">
                                <input type="hidden" name="tracking_enabled" value="0">
                                <input type="checkbox" name="tracking_enabled" value="1" @checked(old('tracking_enabled', $settings->ga_tracking_enabled)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                Enabled
                            </label>
                        </div>

                        <div class="mt-6">
                            <label for="ga-measurement-id" class="ds-field-label">Measurement ID</label>
                            <input id="ga-measurement-id" name="measurement_id" value="{{ old('measurement_id', $settings->ga_measurement_id) }}" autocomplete="off" class="ds-input ds-control font-mono" placeholder="G-XXXXXXXXXX">
                            <p class="ds-help">Google Analytics 4 → Admin → Data Streams → your web stream → Measurement ID.</p>
                            @error('measurement_id')<p class="ds-error">{{ $message }}</p>@enderror
                        </div>
                    </section>

                    <section class="ds-card ds-card-body">
                        <h2 class="ds-section-title">View analytics in this dashboard</h2>
                        <p class="ds-section-description">To show charts on the Analytics page, connect a Google service account with read access to your GA4 property.</p>

                        <ol class="ds-help mt-4 list-decimal space-y-1 pl-5">
                            <li>In Google Cloud Console, create (or reuse) a project and enable the <strong>Google Analytics Data API</strong>.</li>
                            <li>Create a <strong>Service Account</strong>, then create and download a JSON key for it.</li>
                            <li>In Google Analytics → Admin → Property Access Management, add the service account's email as a <strong>Viewer</strong>.</li>
                            <li>Paste the numeric <strong>Property ID</strong> (Admin → Property Settings) and the JSON key content below.</li>
                        </ol>

                        <div class="mt-6 space-y-5">
                            <div>
                                <label for="ga-property-id" class="ds-field-label">Property ID</label>
                                <input id="ga-property-id" name="property_id" value="{{ old('property_id', $settings->ga_property_id) }}" autocomplete="off" class="ds-input ds-control font-mono" placeholder="123456789">
                                @error('property_id')<p class="ds-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="ga-service-account-json" class="ds-field-label">Service account JSON key</label>
                                <textarea id="ga-service-account-json" name="service_account_json" rows="6" autocomplete="off" class="ds-input ds-control font-mono text-xs" placeholder="{{ filled($settings->ga_service_account_json) ? 'Configured — leave blank to keep it' : '{ "type": "service_account", "client_email": "...", "private_key": "..." }' }}"></textarea>
                                <p class="ds-help">Stored encrypted and never shown again.</p>
                                @error('service_account_json')<p class="ds-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="ds-card ds-card-body">
                        <h2 class="ds-section-title">Google Search Console</h2>
                        <p class="ds-section-description">Verify ownership of your site so you can submit sitemaps and monitor search performance.</p>

                        <ol class="ds-help mt-4 list-decimal space-y-1 pl-5">
                            <li>Open <strong>Google Search Console</strong> → add your site as a property.</li>
                            <li>Choose the <strong>HTML tag</strong> verification method.</li>
                            <li>Copy only the <code class="font-mono">content="..."</code> value and paste it below.</li>
                            <li>After saving here, click <strong>Verify</strong> in Search Console.</li>
                        </ol>

                        <div class="mt-6">
                            <label for="search-console-verification" class="ds-field-label">Verification code</label>
                            <input id="search-console-verification" name="search_console_verification" value="{{ old('search_console_verification', $settings->search_console_verification) }}" autocomplete="off" class="ds-input ds-control font-mono">
                            @error('search_console_verification')<p class="ds-error">{{ $message }}</p>@enderror
                        </div>

                        <p class="ds-help mt-4">
                            Sitemap URL to submit in Search Console: <code class="font-mono">{{ url('/sitemap.xml') }}</code>
                        </p>
                    </section>

                    <div class="flex justify-end">
                        <button class="ds-button-primary px-5">Save Google settings</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</x-admin-layout>
