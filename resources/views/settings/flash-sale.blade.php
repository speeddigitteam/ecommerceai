<x-admin-layout title="Flash Sale Settings">
    @php
        $selectedProductIds = collect(old('product_ids', $flashSale['product_ids']))->map(fn ($id) => (int) $id)->all();
        $selectedMode = (string) old('include_all_sale_products', $flashSale['include_all_sale_products'] ? '1' : '0');
    @endphp
    <div x-data="{ menuOpen: false, productMode: @js($selectedMode), productSearch: '' }" class="min-h-screen bg-slate-50 text-slate-900 dark:bg-[#111827] dark:text-slate-100">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />
        <main class="lg:pl-72">
            <x-admin-topbar />
            <div class="mx-auto max-w-5xl p-4 sm:p-7 lg:p-10">
                <div class="mb-6"><p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Website Settings</p><h1 class="mt-1 text-2xl font-bold">Flash Sale</h1><p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Control the banner, countdown, and exactly which discounted products appear on the Flash Sale page.</p></div>
                @if (session('status'))<div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>@endif
                <form method="POST" action="{{ route('settings.flash-sale.update') }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-[#161f2e] sm:p-6"><div class="flex items-start justify-between gap-5"><div><h2 class="font-bold">Banner visibility</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Turn the homepage Flash Sale section on or off.</p></div><label class="inline-flex cursor-pointer items-center gap-3"><input type="hidden" name="enabled" value="0"><input type="checkbox" name="enabled" value="1" @checked(old('enabled', $flashSale['enabled'])) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"><span class="text-sm font-semibold">Enabled</span></label></div></section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-[#161f2e] sm:p-6">
                        <h2 class="font-bold">Flash Sale products</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Only published, public products with a valid sale price are eligible.</p>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <label class="flex cursor-pointer gap-3 rounded-xl border p-4 transition" :class="productMode === '1' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-slate-700'"><input type="radio" name="include_all_sale_products" value="1" x-model="productMode" class="mt-1 text-indigo-600 focus:ring-indigo-500"><span><strong class="block text-sm">All sale products</strong><small class="mt-1 block text-slate-500 dark:text-slate-400">Automatically include every eligible discounted product.</small></span></label>
                            <label class="flex cursor-pointer gap-3 rounded-xl border p-4 transition" :class="productMode === '0' ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-500/10' : 'border-slate-200 dark:border-slate-700'"><input type="radio" name="include_all_sale_products" value="0" x-model="productMode" class="mt-1 text-indigo-600 focus:ring-indigo-500"><span><strong class="block text-sm">Selected products only</strong><small class="mt-1 block text-slate-500 dark:text-slate-400">Show only the products you select below.</small></span></label>
                        </div>
                        @error('include_all_sale_products')<p class="ds-error mt-2">{{ $message }}</p>@enderror

                        <div x-cloak x-show="productMode === '0'" x-transition class="mt-5 rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                            <label for="flash-product-search" class="ds-label">Search and select products</label><input id="flash-product-search" type="search" x-model="productSearch" placeholder="Search by product name or SKU..." class="ds-input">
                            <div class="mt-3 max-h-96 space-y-2 overflow-y-auto pr-1">
                                @forelse ($saleProducts as $product)
                                    <label x-show="@js(strtolower($product->title.' '.$product->sku)).includes(productSearch.toLowerCase())" class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-3 hover:border-indigo-300 hover:bg-indigo-50/50 dark:border-slate-700 dark:hover:bg-indigo-500/10">
                                        <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array($product->id, $selectedProductIds, true)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                        <span class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-800">@if ($product->featured_image_path)<img src="{{ asset('storage/'.$product->featured_image_path) }}" alt="" class="h-full w-full object-contain">@endif</span>
                                        <span class="min-w-0 flex-1"><strong class="block truncate text-sm">{{ $product->title }}</strong><small class="mt-1 block text-xs text-slate-500">{{ $product->sku ?: 'No SKU' }} &middot; <span class="line-through">{{ number_format((float) $product->price, 2) }}</span> &rarr; {{ number_format((float) $product->sale_price, 2) }}</small></span>
                                    </label>
                                @empty
                                    <div class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500 dark:border-slate-700">No eligible sale products found. Add a sale price to a published product first.</div>
                                @endforelse
                            </div>
                            @error('product_ids')<p class="ds-error mt-2">{{ $message }}</p>@enderror
                            @error('product_ids.*')<p class="ds-error mt-2">{{ $message }}</p>@enderror
                        </div>
                    </section>

                    <section class="grid gap-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-[#161f2e] sm:grid-cols-2 sm:p-6"><div class="sm:col-span-2"><h2 class="font-bold">Content and countdown</h2></div><label class="block"><span class="ds-label">Small label</span><input name="eyebrow" value="{{ old('eyebrow', $flashSale['eyebrow']) }}" class="ds-input" required>@error('eyebrow')<span class="ds-error">{{ $message }}</span>@enderror</label><label class="block"><span class="ds-label">Title</span><input name="title" value="{{ old('title', $flashSale['title']) }}" class="ds-input" required>@error('title')<span class="ds-error">{{ $message }}</span>@enderror</label><label class="block sm:col-span-2"><span class="ds-label">Description</span><textarea name="description" rows="3" class="ds-input" required>{{ old('description', $flashSale['description']) }}</textarea>@error('description')<span class="ds-error">{{ $message }}</span>@enderror</label><label class="block"><span class="ds-label">Countdown end date and time</span><input type="datetime-local" name="ends_at" value="{{ old('ends_at', filled($flashSale['ends_at']) ? \Carbon\Carbon::parse($flashSale['ends_at'])->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i') : '') }}" class="ds-input">@error('ends_at')<span class="ds-error">{{ $message }}</span>@enderror</label><label class="block"><span class="ds-label">Button text</span><input name="button_text" value="{{ old('button_text', $flashSale['button_text']) }}" class="ds-input" required>@error('button_text')<span class="ds-error">{{ $message }}</span>@enderror</label></section>
                    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-[#161f2e] sm:p-6"><h2 class="font-bold">Brand gradient</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Choose the three colors used across the banner.</p><div class="mt-5 grid gap-5 sm:grid-cols-3">@foreach (['background_from' => 'Start color', 'background_via' => 'Middle color', 'background_to' => 'End color'] as $field => $label)<label class="block"><span class="ds-label">{{ $label }}</span><div class="flex gap-2"><input type="color" value="{{ old($field, $flashSale[$field]) }}" class="h-11 w-14 rounded-lg border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-900" oninput="this.nextElementSibling.value=this.value"><input name="{{ $field }}" value="{{ old($field, $flashSale[$field]) }}" class="ds-input font-mono" required></div>@error($field)<span class="ds-error">{{ $message }}</span>@enderror</label>@endforeach</div></section>
                    <div class="flex justify-end"><button class="ds-button-primary px-5">Save Flash Sale</button></div>
                </form>
            </div>
        </main>
    </div>
</x-admin-layout>