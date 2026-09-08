<x-admin-layout title="Hero Settings">
    <div class="ds-page">
        <div x-cloak x-show="menuOpen" @click="menuOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>
        <x-admin-sidebar />

        <main class="min-w-0 lg:pl-72">
            <x-admin-topbar />

            <div class="mx-auto max-w-5xl p-5 sm:p-8">
                <div class="mb-6">
                    <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">Settings</p>
                    <h1 class="mt-1 text-2xl font-bold">Hero settings</h1>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Control the storefront heading, featured product, and center image slider.</p>
                </div>

                @if (session('status'))
                    <div class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-300">Hero settings could not be saved. Please check the highlighted fields below.</div>
                @endif

                <form data-ds-editable data-ds-editable-start="edit" method="POST" action="{{ route('settings.hero.update') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <section class="ds-card ds-card-body">
                        <h2 class="ds-section-title">Hero content</h2>
                        <p class="ds-section-description">Leave text fields empty to use the storefront defaults.</p>

                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="hero_title" class="ds-field-label">Heading</label>
                                <input id="hero_title" name="hero_title" value="{{ old('hero_title', $settings->hero_title) }}" placeholder="New arrivals" class="ds-input ds-control">
                                @error('hero_title')<p class="ds-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="hero_subtitle" class="ds-field-label">Second heading</label>
                                <input id="hero_subtitle" name="hero_subtitle" value="{{ old('hero_subtitle', $settings->hero_subtitle) }}" placeholder="Uses the featured product title by default" class="ds-input ds-control">
                                @error('hero_subtitle')<p class="ds-error">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        @php($heroProductOptions = $products->map(fn ($product) => ['id' => (string) $product->id, 'title' => $product->title, 'sku' => $product->sku, 'image' => $product->featured_image_path ? asset('storage/'.$product->featured_image_path) : null])->values())
                        <div x-data="{ open: false, query: '', selectedProductId: @js((string) old('hero_product_id', $settings->hero_product_id ?? '')), products: @js($heroProductOptions), get selectedProduct() { return this.products.find(product => product.id === this.selectedProductId) }, get filteredProducts() { const search = this.query.trim().toLowerCase(); return this.products.filter(product => !search || product.title.toLowerCase().includes(search) || (product.sku ?? '').toLowerCase().includes(search)).slice(0, 50) }, choose(productId) { this.selectedProductId = productId; this.open = false; this.query = '' } }" @click.outside="open = false" class="relative mt-6">
                            <span class="ds-field-label">Featured product</span>
                            <input type="hidden" name="hero_product_id" :value="selectedProductId">
                            <button type="button" @click="open = !open; $nextTick(() => open && $refs.productSearch.focus())" class="mt-3 flex w-full items-center gap-3 rounded-xl border border-slate-300 bg-white p-3 text-left transition hover:border-indigo-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800">
                                <span class="h-12 w-12 shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-700">
                                    <template x-if="selectedProduct?.image"><img :src="selectedProduct.image" alt="" class="h-full w-full object-cover"></template>
                                    <template x-if="!selectedProduct?.image"><span class="grid h-full w-full place-items-center text-slate-400"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.8" d="M4 12a8 8 0 1 0 2.3-5.7M4 4v5h5"/></svg></span></template>
                                </span>
                                <span class="min-w-0 flex-1"><strong class="block truncate text-sm" x-text="selectedProduct?.title ?? 'Automatic selection'"></strong><small class="mt-1 block truncate text-xs text-slate-500" x-text="selectedProduct ? (selectedProduct.sku || 'No SKU') : 'Use the latest published product'"></small></span>
                                <svg class="h-5 w-5 shrink-0 text-slate-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg>
                            </button>

                            <div x-cloak x-show="open" x-transition class="absolute inset-x-0 z-20 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800">
                                <div class="border-b border-slate-200 p-3 dark:border-slate-700">
                                    <label for="hero_product_search" class="sr-only">Search products</label>
                                    <input id="hero_product_search" x-ref="productSearch" x-model="query" @keydown.escape="open = false" type="search" placeholder="Search by product title or SKU..." class="ds-input ds-control">
                                </div>
                                <div class="max-h-72 overflow-y-auto p-2">
                                    <button type="button" @click="choose('')" class="flex w-full items-center gap-3 rounded-lg p-2 text-left transition hover:bg-slate-100 dark:hover:bg-slate-700">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-slate-100 text-slate-400 dark:bg-slate-700"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.8" d="M4 12a8 8 0 1 0 2.3-5.7M4 4v5h5"/></svg></span>
                                        <span><strong class="block text-sm">Automatic selection</strong><small class="text-xs text-slate-500">Latest published product</small></span>
                                    </button>
                                    <template x-for="product in filteredProducts" :key="product.id">
                                        <button type="button" @click="choose(product.id)" class="mt-1 flex w-full items-center gap-3 rounded-lg p-2 text-left transition hover:bg-indigo-50 dark:hover:bg-indigo-500/10" :class="selectedProductId === product.id && 'bg-indigo-50 dark:bg-indigo-500/10'">
                                            <span class="h-10 w-10 shrink-0 overflow-hidden rounded-lg bg-slate-100 dark:bg-slate-700"><template x-if="product.image"><img :src="product.image" alt="" class="h-full w-full object-cover"></template></span>
                                            <span class="min-w-0 flex-1"><strong class="block truncate text-sm" x-text="product.title"></strong><small class="block truncate text-xs text-slate-500" x-text="product.sku || 'No SKU'"></small></span>
                                            <svg x-show="selectedProductId === product.id" class="h-5 w-5 shrink-0 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>
                                        </button>
                                    </template>
                                    <p x-show="filteredProducts.length === 0" class="px-3 py-8 text-center text-sm text-slate-500">No matching products found.</p>
                                </div>
                            </div>
                            <p class="ds-help">Search by title or SKU. Up to 50 matching products are shown at once.</p>
                            @error('hero_product_id')<p class="ds-error">{{ $message }}</p>@enderror
                        </div>
                    </section>

                    <section class="ds-card ds-card-body">
                        <h2 class="ds-section-title">Center slider images</h2>
                        <p class="ds-section-description">Upload up to 8 images. If none are added, a neutral placeholder image is shown.</p>

                        @if ($settings->hero_slider_paths)
                            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($settings->hero_slider_paths as $path)
                                    <label class="group relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                                        <img src="{{ asset('storage/'.$path) }}" alt="Hero slide {{ $loop->iteration }}" class="aspect-[4/3] w-full object-cover">
                                        <span class="flex items-center gap-2 p-3 text-xs font-semibold text-rose-600">
                                            <input type="checkbox" name="remove_slider_images[]" value="{{ $path }}" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                            Remove this image
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <div x-data="{ maxInputs: {{ max(0, 8 - count($settings->hero_slider_paths ?? [])) }}, imageInputs: {{ count($settings->hero_slider_paths ?? []) < 8 ? '[0]' : '[]' }}, nextInput: 1 }" class="mt-6 rounded-xl border border-dashed border-slate-300 p-5 dark:border-slate-700">
                            <span class="ds-field-label">Add slider images</span>
                            <div class="mt-3 space-y-3">
                                <template x-for="(inputKey, index) in imageInputs" :key="inputKey">
                                    <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                                        <label :for="`slider_image_${inputKey}`" class="sr-only" x-text="`Slider image ${index + 1}`"></label>
                                        <input :id="`slider_image_${inputKey}`" name="slider_images[]" type="file" accept="image/png,image/jpeg,image/webp" class="min-w-0 flex-1 text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700 dark:file:bg-indigo-500/15 dark:file:text-indigo-300">
                                        <button x-show="imageInputs.length > 1" type="button" @click="imageInputs.splice(index, 1)" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-rose-600 transition hover:bg-rose-50 dark:hover:bg-rose-500/10" :aria-label="`Remove image field ${index + 1}`">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m6 6 12 12M18 6 6 18"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <button type="button" @click="if (imageInputs.length < maxInputs) imageInputs.push(nextInput++)" :disabled="imageInputs.length >= maxInputs" class="mt-4 inline-flex items-center gap-2 rounded-lg border border-indigo-200 px-3 py-2 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50 disabled:cursor-not-allowed disabled:opacity-50 dark:border-indigo-500/30 dark:text-indigo-300 dark:hover:bg-indigo-500/10">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14M5 12h14"/></svg>
                                Add another image
                            </button>
                            <p class="ds-help">Recommended size: 1200 × 900 px (4:3). PNG, JPG, or WebP. Maximum 4 MB per image.</p>
                            @error('slider_images')<p class="ds-error">{{ $message }}</p>@enderror
                            @error('slider_images.*')<p class="ds-error">{{ $message }}</p>@enderror
                        </div>
                    </section>

                    <section class="ds-card ds-card-body">
                        <h2 class="ds-section-title">Right-side hero images</h2>
                        <p class="ds-section-description">Upload the two images displayed side by side to the right of the hero slider.</p>

                        <div class="mt-6 grid gap-5 sm:grid-cols-2">
                            @foreach (['one' => 'First image', 'two' => 'Second image'] as $slot => $label)
                                @php($pathAttribute = 'hero_side_image_'.$slot.'_path')
                                <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                                    <label for="hero_side_image_{{ $slot }}" class="ds-field-label">{{ $label }}</label>

                                    @if ($settings->{$pathAttribute})
                                        <img src="{{ asset('storage/'.$settings->{$pathAttribute}) }}" alt="{{ $label }} preview" class="mt-3 h-52 w-full rounded-xl object-cover">
                                        <label class="mt-3 flex items-center gap-2 text-sm font-medium text-rose-600">
                                            <input type="checkbox" name="remove_hero_side_image_{{ $slot }}" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500">
                                            Remove current image
                                        </label>
                                    @endif

                                    <input id="hero_side_image_{{ $slot }}" name="hero_side_image_{{ $slot }}" type="file" accept="image/png,image/jpeg,image/webp" class="mt-4 block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700 dark:file:bg-indigo-500/15 dark:file:text-indigo-300">
                                    <p class="ds-help">Recommended size: 300 × 400 px (3:4). PNG, JPG, or WebP; maximum 4 MB.</p>
                                    @error('hero_side_image_'.$slot)<p class="ds-error">{{ $message }}</p>@enderror
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <div class="flex justify-end">
                        <button class="ds-button-primary px-5">Save hero settings</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</x-admin-layout>
