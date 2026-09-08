<x-admin-layout title="Website Settings">
    <div class="ds-page">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />

        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />

            <div class="mx-auto max-w-5xl p-5 sm:p-8">
                <div class="mb-6">
                    <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Settings</p>
                    <h1 class="mt-1 text-2xl font-bold">Website settings</h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Manage your website identity and search engine metadata.</p>
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('settings.website.update') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <section class="ds-card ds-card-body">
                        <h2 class="ds-section-title">Site identity</h2>
                        <p class="ds-section-description">The name and images shown throughout the website.</p>

                        <div class="mt-6">
                            <label for="site_name" class="ds-field-label">Site name</label>
                            <input id="site_name" name="site_name" value="{{ old('site_name', $settings->site_name) }}" required class="ds-input ds-control">
                            @error('site_name')<p class="ds-error">{{ $message }}</p>@enderror
                        </div>

                        <div class="mt-6 grid gap-5 md:grid-cols-2">
                            <div><label for="business_phone" class="ds-field-label">Business phone</label><input id="business_phone" name="business_phone" value="{{ old('business_phone', $settings->business_phone) }}" class="ds-input ds-control" placeholder="01XXXXXXXXX">@error('business_phone')<p class="ds-error">{{ $message }}</p>@enderror</div>
                            <div><label for="website_url" class="ds-field-label">Website URL</label><input id="website_url" type="url" name="website_url" value="{{ old('website_url', $settings->website_url ?: url('/')) }}" class="ds-input ds-control" placeholder="https://example.com">@error('website_url')<p class="ds-error">{{ $message }}</p>@enderror</div>
                            <div class="md:col-span-2"><label for="business_address" class="ds-field-label">Sender / business address</label><textarea id="business_address" name="business_address" rows="3" class="ds-textarea ds-control" placeholder="Pickup address printed on parcel labels">{{ old('business_address', $settings->business_address) }}</textarea>@error('business_address')<p class="ds-error">{{ $message }}</p>@enderror</div>
                        </div>

                        <div class="mt-6 grid gap-6 md:grid-cols-2">
                            <div>
                                <label for="logo" class="text-sm font-semibold">Logo</label>
                                <div class="mt-2 flex min-h-28 items-center gap-4 rounded-xl border border-dashed border-slate-300 p-4 dark:border-slate-700">
                                    @if ($settings->logo_path)<img src="{{ asset('storage/'.$settings->logo_path) }}" alt="Current logo" class="h-16 w-24 rounded-lg object-contain">@endif
                                    <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="min-w-0 text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700 dark:file:bg-indigo-500/15 dark:file:text-indigo-300">
                                </div>
                                <p class="mt-2 text-xs text-slate-500">PNG, JPG or WebP. Maximum 2 MB.</p>
                                @error('logo')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div>
                                <label for="favicon" class="text-sm font-semibold">Favicon</label>
                                <div class="mt-2 flex min-h-28 items-center gap-4 rounded-xl border border-dashed border-slate-300 p-4 dark:border-slate-700">
                                    @if ($settings->favicon_path)<img src="{{ asset('storage/'.$settings->favicon_path) }}" alt="Current favicon" class="h-12 w-12 rounded-lg object-contain">@endif
                                    <input id="favicon" name="favicon" type="file" accept="image/png,image/x-icon,image/vnd.microsoft.icon,image/jpeg,image/webp,.ico" class="min-w-0 text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700 dark:file:bg-indigo-500/15 dark:file:text-indigo-300">
                                </div>
                                <p class="mt-2 text-xs text-slate-500">Recommended: 32 x 32 or 64 x 64 px. PNG, ICO, JPG or WebP; maximum 1 MB.</p>
                                @error('favicon')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>

                    <section class="ds-card overflow-hidden">
                        <div class="ds-card-header">
                            <div class="flex items-start gap-3">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="m20 20-4-4"/></svg></span>
                                <div><h2 class="text-lg font-bold">Search engine optimization</h2><p class="mt-1 text-sm text-slate-500">Control how your website appears in search results and social shares.</p></div>
                            </div>
                        </div>

                        <div class="space-y-8 p-5 sm:p-7">
                            <div>
                                <div class="mb-4"><p class="text-sm font-bold">Search appearance</p><p class="mt-1 text-xs text-slate-500">The default title and description shown by search engines.</p></div>
                                <div class="ds-search-preview mb-5">
                                    <p class="text-xs text-emerald-700 dark:text-emerald-400">{{ url('/') }}</p>
                                    <p class="mt-1 truncate text-lg font-medium text-blue-700 dark:text-blue-400">{{ old('seo_title', $settings->seo_title) }} | {{ old('site_name', $settings->site_name) }}</p>
                                    <p class="mt-1 line-clamp-2 text-sm leading-5 text-slate-600 dark:text-slate-400">{{ old('meta_description', $settings->meta_description) ?: 'Your meta description preview will appear here.' }}</p>
                                </div>

                                <div class="grid gap-5">
                                    <div x-data="{ value: @js(old('seo_title', $settings->seo_title)) }">
                                        <div class="ds-field-header"><label for="seo_title" class="ds-field-label">SEO title</label><span class="text-xs text-slate-400">Ideal: 50-60</span></div>
                                        <input id="seo_title" name="seo_title" x-model="value" required aria-describedby="seo-title-progress" class="ds-input ds-control">
                                        <div id="seo-title-progress" class="mt-3">
                                            <div class="h-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700" role="progressbar" aria-valuemin="0" aria-valuemax="60" :aria-valuenow="value.length"><div class="h-full rounded-full transition-all duration-200" :class="value.length === 0 ? 'bg-slate-400' : value.length < 50 ? 'bg-amber-400' : value.length <= 60 ? 'bg-emerald-500' : 'bg-rose-600'" :style="`width: ${Math.min((value.length / 60) * 100, 100)}%`"></div></div>
                                            <div class="mt-1.5 flex justify-between text-xs"><span :class="value.length === 0 ? 'text-slate-400' : value.length < 50 ? 'text-amber-600' : value.length <= 60 ? 'text-emerald-600' : 'font-semibold text-rose-600'" x-text="value.length === 0 ? 'Start typing' : value.length > 60 ? 'Too long' : value.length >= 50 ? 'Ideal length' : 'Keep writing'"></span><span class="tabular-nums text-slate-500"><span x-text="value.length"></span>/60</span></div>
                                        </div>
                                        @error('seo_title')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div x-data="{ value: @js(old('meta_description', $settings->meta_description ?? '')) }">
                                        <div class="ds-field-header"><label for="meta_description" class="ds-field-label">Meta description</label><span class="text-xs text-slate-400">Ideal: 150-160</span></div>
                                        <textarea id="meta_description" name="meta_description" x-model="value" rows="3" aria-describedby="meta-description-progress" class="ds-textarea ds-control"></textarea>
                                        <div id="meta-description-progress" class="mt-3">
                                            <div class="h-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700" role="progressbar" aria-valuemin="0" aria-valuemax="160" :aria-valuenow="value.length"><div class="h-full rounded-full transition-all duration-200" :class="value.length === 0 ? 'bg-slate-400' : value.length < 150 ? 'bg-amber-400' : value.length <= 160 ? 'bg-emerald-500' : 'bg-rose-600'" :style="`width: ${Math.min((value.length / 160) * 100, 100)}%`"></div></div>
                                            <div class="mt-1.5 flex justify-between text-xs"><span :class="value.length === 0 ? 'text-slate-400' : value.length < 150 ? 'text-amber-600' : value.length <= 160 ? 'text-emerald-600' : 'font-semibold text-rose-600'" x-text="value.length === 0 ? 'Start typing' : value.length > 160 ? 'Too long' : value.length >= 150 ? 'Ideal length' : 'Keep writing'"></span><span class="tabular-nums text-slate-500"><span x-text="value.length"></span>/160</span></div>
                                        </div>
                                        @error('meta_description')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>

                            <div x-data="{ keywords: @js(array_values(array_filter(array_map('trim', explode(',', old('meta_keywords', $settings->meta_keywords ?? '')))))), keyword: '', addKeyword() { this.keyword.split(',').map(item => item.trim()).filter(Boolean).forEach(item => { if (!this.keywords.includes(item)) this.keywords.push(item) }); this.keyword = '' } }" class="border-t border-slate-200 pt-8 dark:border-slate-800">
                                <div class="mb-4"><p class="text-sm font-bold">Search keywords</p><p class="mt-1 text-xs text-slate-500">Add relevant terms that describe your website and products.</p></div>
                                <input id="meta_keywords" name="meta_keywords" type="hidden" :value="keywords.join(', ')">
                                <div class="rounded-xl border border-slate-300 bg-white p-3 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800">
                                    <div x-show="keywords.length" class="mb-3 flex flex-wrap gap-2">
                                        <template x-for="(item, index) in keywords" :key="item"><span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300"><span x-text="item"></span><button type="button" @click="keywords.splice(index, 1)" class="text-indigo-400 transition hover:text-rose-600" :aria-label="`Remove ${item}`"><svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m6 6 12 12M18 6 6 18"/></svg></button></span></template>
                                    </div>
                                    <label for="meta_keyword_input" class="sr-only">Add a keyword</label><input id="meta_keyword_input" x-model="keyword" @keydown.enter.prevent="addKeyword()" @keydown="if ($event.key === ',') { $event.preventDefault(); addKeyword() }" @blur="addKeyword()" type="text" placeholder="Type a keyword and press Enter" class="w-full border-0 bg-transparent p-1 text-sm focus:ring-0 dark:text-slate-100">
                                </div>
                                <div class="mt-2 flex items-center justify-between gap-3 text-xs text-slate-500"><p>Press Enter or comma after each keyword.</p><p><span x-text="keywords.length"></span> keywords</p></div>
                                @error('meta_keywords')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="border-t border-slate-200 pt-8 dark:border-slate-800">
                                <div class="mb-4"><p class="text-sm font-bold">Social sharing image</p><p class="mt-1 text-xs text-slate-500">Displayed when your website is shared on Facebook, X, LinkedIn, and other platforms.</p></div>
                                <label for="featured_image" class="sr-only">Featured image</label>
                                <div class="flex min-h-40 flex-col gap-5 rounded-xl border border-dashed border-slate-300 bg-slate-50/60 p-4 dark:border-slate-700 dark:bg-slate-900/30 sm:flex-row sm:items-center">
                                    @if ($settings->featured_image_path)<img src="{{ asset('storage/'.$settings->featured_image_path) }}" alt="Current featured image" class="aspect-[1200/630] w-full rounded-lg border border-slate-200 object-cover dark:border-slate-700 sm:w-56">@else<div class="grid aspect-[1200/630] w-full place-items-center rounded-lg bg-slate-100 text-slate-400 dark:bg-slate-800 sm:w-56"><svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2" stroke-width="2"/><circle cx="9" cy="10" r="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4 17 5-4 4 3 3-2 4 3"/></svg></div>@endif
                                    <div class="min-w-0"><input id="featured_image" name="featured_image" type="file" accept="image/png,image/jpeg,image/webp" class="max-w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700 dark:file:bg-indigo-500/15 dark:file:text-indigo-300"><p class="mt-3 text-xs leading-5 text-slate-500">Recommended: 1200 × 630 px<br>PNG, JPG or WebP · Maximum 4 MB</p></div>
                                </div>
                                @error('featured_image')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>

                    <div class="flex justify-end">
                        <button class="ds-button-primary px-5">Save settings</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</x-admin-layout>
