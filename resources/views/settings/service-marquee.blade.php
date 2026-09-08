<x-admin-layout title="Service Marquee">
    <div class="ds-page">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />

        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />

            <div class="mx-auto max-w-4xl p-5 sm:p-8">
                <div class="mb-6">
                    <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Settings</p>
                    <h1 class="mt-1 text-2xl font-bold">Service Marquee</h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Manage the scrolling service messages shown below the storefront hero.</p>
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ session('status') }}</div>
                @endif

                <form data-ds-editable data-ds-editable-start="edit" method="POST" action="{{ route('settings.service-marquee.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <section class="ds-card ds-card-body">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="ds-section-title">Marquee content</h2>
                                <p class="ds-section-description">Edit all five messages and control their scrolling speed.</p>
                            </div>
                            <label class="flex items-center gap-2 text-sm font-semibold">
                                <input type="hidden" name="enabled" value="0">
                                <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings->service_marquee_enabled ?? true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                Enabled
                            </label>
                        </div>

                        @php
                            $savedItems = $settings->service_marquee_items ?: $defaultItems;
                            $items = old('items', collect($savedItems)->values()->map(fn ($item, $index) => is_array($item) ? $item : ['label' => $item, 'icon' => $defaultItems[$index]['icon']])->all());
                        @endphp
                        <div class="mt-6 space-y-4">
                            @foreach ($items as $index => $item)
                                <div class="grid gap-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700 sm:grid-cols-[minmax(0,1fr)_220px]">
                                    <label for="item_{{ $index }}" class="ds-field-label">Message {{ $index + 1 }}</label>
                                    <label for="icon_{{ $index }}" class="ds-field-label">Icon</label>
                                    <div>
                                        <input id="item_{{ $index }}" name="items[{{ $index }}][label]" value="{{ $item['label'] }}" class="ds-input ds-control" required maxlength="160">
                                        @error('items.'.$index.'.label')<p class="ds-error">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <select id="icon_{{ $index }}" name="items[{{ $index }}][icon]" class="ds-select ds-control" required>
                                            @foreach ($iconOptions as $icon => $iconLabel)
                                                <option value="{{ $icon }}" @selected($item['icon'] === $icon)>{{ $iconLabel }}</option>
                                            @endforeach
                                        </select>
                                        @error('items.'.$index.'.icon')<p class="ds-error">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-6 max-w-xs">
                            <label for="speed" class="ds-field-label">Animation duration (seconds)</label>
                            <input id="speed" name="speed" type="number" min="10" max="120" value="{{ old('speed', $settings->service_marquee_speed ?? 28) }}" class="ds-input ds-control" required>
                            <p class="ds-help">Lower values move faster. Allowed range: 10–120 seconds.</p>
                            @error('speed')<p class="ds-error">{{ $message }}</p>@enderror
                        </div>
                    </section>

                    <div class="flex justify-end">
                        <button class="ds-button-primary px-5">Save marquee settings</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</x-admin-layout>
