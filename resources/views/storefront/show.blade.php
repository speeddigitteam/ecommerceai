<x-storefront-layout :title="$product->seo_title ?: $product->title" :description="$product->meta_description ?: $product->short_description" og-type="product">
    @php
        $deliveryRates = app(App\Services\DeliveryCharges::class)->rates([$product]);
        $breadcrumbCategories = collect();
        $breadcrumbCategory = $product->categories->sortByDesc(fn ($category) => $category->parentRecursive ? 1 : 0)->first();
        while ($breadcrumbCategory) {
            $breadcrumbCategories->prepend($breadcrumbCategory);
            $breadcrumbCategory = $breadcrumbCategory->parentRecursive;
        }
        $productUrl = route('catalog.show', $product->slug);
        $cartQuantity = (int) data_get(session('cart', []), $product->id, 0);
        $productSpecifications = collect($product->specifications ?? []);
        $productVariants = $product->variants;
        $optionGroups = $productVariants
            ->flatMap(fn ($variant) => collect($variant->options ?? [])->keys())
            ->unique()
            ->map(fn ($name) => [
                'name' => $name,
                'values' => $productVariants->pluck("options.{$name}")->filter()->unique()->values()->all(),
            ])->values();

    @endphp
    <div class="mx-auto max-w-[1400px] px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
        <nav aria-label="Breadcrumb" class="flex min-w-0 items-center gap-2 overflow-hidden text-xs text-slate-600 sm:text-sm">
            <a href="{{ route('storefront.index') }}" aria-label="Home" class="shrink-0 transition hover:text-indigo-600"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3 2.5 11h2.2v9h5.5v-5.5h3.6V20h5.5v-9h2.2L12 3Z"/></svg></a>
            @foreach ($breadcrumbCategories as $category)
                <span class="shrink-0 text-slate-300">/</span>
                <a href="{{ route('catalog.show', $category->slug) }}" class="shrink-0 transition hover:text-indigo-600">{{ $category->name }}</a>
            @endforeach
            @if ($product->brand?->slug)
                <span class="shrink-0 text-slate-300">/</span>
                <a href="{{ route('catalog.show', $product->brand->slug) }}" class="shrink-0 transition hover:text-indigo-600">{{ $product->brand->name }}</a>
            @endif
            <span class="shrink-0 text-slate-300">/</span>
            <span class="truncate text-slate-500" aria-current="page">{{ $product->title }}</span>
        </nav>

        <div
            x-data="{
                cartProductQuantity: {{ $cartQuantity }},
                variants: @js($productVariants->map(fn ($variant) => ['id' => $variant->id, 'sku' => $variant->sku, 'price' => $variant->price !== null ? (float) $variant->price : null, 'sale_price' => $variant->sale_price !== null ? (float) $variant->sale_price : null, 'stock_quantity' => $variant->stock_quantity, 'image_path' => $variant->image_path, 'options' => $variant->options ?? []])->values()),
                optionGroups: @js($optionGroups),
                selections: {},
                quantity: 1,
                adding: false,
                added: false,
                get hasVariants() { return this.variants.length > 0 },
                get selectedVariant() {
                    if (! this.hasVariants || Object.keys(this.selections).length !== this.optionGroups.length) return null;
                    return this.variants.find(variant => this.optionGroups.every(group => variant.options[group.name] === this.selections[group.name])) || null;
                },
                get currentPrice() { const v = this.selectedVariant; return v ? (v.sale_price ?? v.price) : {{ (float) $product->current_price }}; },
                get currentRegularPrice() { const v = this.selectedVariant; return v ? v.price : {{ (float) ($product->price ?? 0) }}; },
                get currentSalePrice() { const v = this.selectedVariant; return v ? v.sale_price : {{ $product->sale_price !== null ? (float) $product->sale_price : 'null' }}; },
                get currentStock() { if (this.hasVariants) return this.selectedVariant ? this.selectedVariant.stock_quantity : 0; return {{ $product->stock_quantity }}; },
                get currentSku() { const v = this.selectedVariant; return v ? v.sku : @js($product->sku); },
                money(value) { return new Intl.NumberFormat('en-US', { maximumFractionDigits: 0 }).format(value) },
                selectOption(name, value) {
                    this.selections = { ...this.selections, [name]: value };
                    this.quantity = 1;
                    const variant = this.selectedVariant;
                    window.dispatchEvent(new CustomEvent('variant-selected', { detail: { id: variant?.id ?? null, price: this.currentPrice, stock: this.currentStock, image: variant?.image_path ?? null } }));
                },
                async addToCart() {
                    if (this.adding || (this.hasVariants && ! this.selectedVariant)) return;
                    this.adding = true; this.added = false;
                    try {
                        const response = await fetch(this.$refs.addToCartForm.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: new FormData(this.$refs.addToCartForm) });
                        if (! response.ok) return;
                        const data = await response.json();
                        this.added = true;
                        this.cartProductQuantity = data.productQuantity;
                        window.dispatchEvent(new CustomEvent('cart-count-updated', { detail: { count: data.cartCount } }));
                        window.dispatchEvent(new CustomEvent('cart-notification', { detail: { message: 'Product added to your cart.' } }));
                        window.setTimeout(() => this.added = false, 1800);
                    } finally { this.adding = false; }
                }
            }"
            class="mt-5 grid gap-6 lg:grid-cols-2 lg:gap-8"
            @product-cart-updated="cartProductQuantity = $event.detail.quantity"
        >
            @php
                $productImages = collect([$product->featured_image_path])
                    ->merge($product->gallery_paths ?? [])
                    ->filter()
                    ->unique()
                    ->values();
            @endphp
            <div x-data="{ activeImage: @js($productImages->first()) }" @variant-selected.window="if ($event.detail.image) activeImage = $event.detail.image" class="min-w-0">
                <div class="{{ $productImages->count() > 1 ? 'grid grid-cols-[76px_minmax(0,1fr)] items-start gap-3 sm:grid-cols-[92px_minmax(0,1fr)] sm:gap-4' : '' }}">
                    @if ($productImages->count() > 1)
                        <div class="flex max-h-[min(76vw,580px)] flex-col gap-3 overflow-y-auto pr-1">
                            @foreach ($productImages as $image)
                                <button type="button" @click="activeImage = @js($image)" class="aspect-square w-full shrink-0 overflow-hidden rounded-xl border-0 bg-white p-1 transition" :class="activeImage === @js($image) ? 'bg-indigo-50 shadow-sm' : 'hover:bg-slate-100'" aria-label="View product image {{ $loop->iteration }}">
                                    <img src="{{ asset('storage/'.$image) }}" alt="" class="h-full w-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                    @if ($productImages->isNotEmpty())
                        <div class="relative aspect-square min-w-0 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                            <img :src="`{{ asset('storage') }}/${activeImage}`" alt="{{ $product->title }}" class="h-full w-full object-cover">
                            <span x-cloak x-show="cartProductQuantity > 0" class="absolute right-3 top-3 rounded-full bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white shadow-sm sm:right-4 sm:top-4">In Cart &middot; Qty <span x-text="cartProductQuantity">{{ $cartQuantity }}</span></span>
                        </div>
                    @else
                    <div class="relative aspect-square min-w-0 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div class="grid h-full place-items-center text-slate-300"><svg class="h-24 w-24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.2" d="M4 5h16v14H4zM4 16l4-4 4 4 3-3 5 5"/></svg></div>
                    </div>
                    @endif
                </div>
            </div>
            <div class="lg:py-1">
                <h1 class="text-2xl font-normal leading-tight text-indigo-700 sm:text-[28px]">{{ $product->title }}</h1>
                <div class="mt-3 rounded-xl border border-slate-200 p-3 text-sm"><strong>{{ max($deliveryRates) === 0.0 ? 'Free Delivery' : 'Delivery charge' }}</strong>@if(max($deliveryRates) > 0)<div class="mt-2 flex flex-wrap gap-3 text-slate-500">@foreach(App\Services\DeliveryCharges::AREAS as $area => $label)<span>{{ $label }}: &#2547;{{ number_format($deliveryRates[$area], 2) }}</span>@endforeach</div>@endif</div>
                <div class="mt-3 flex flex-wrap gap-2 text-sm text-slate-600">
                    <span class="rounded-xl border border-amber-300 px-3 py-2">Price: <strong class="font-bold text-slate-950" x-text="money(currentPrice) + '৳'">{{ number_format($product->current_price, 0) }}৳</strong><template x-if="currentSalePrice"><strong class="ml-1 font-bold text-slate-400 line-through" x-text="money(currentRegularPrice) + '৳'">@if($product->sale_price !== null){{ number_format($product->price, 0) }}৳@endif</strong></template></span>
                    @if ($product->isDigital())
                        <span class="rounded-lg bg-indigo-50 px-2.5 py-1.5 font-semibold text-indigo-700">Instant download</span>
                    @else
                        <span class="rounded-lg bg-slate-100 px-2.5 py-1.5 font-normal" :class="currentStock > 0 ? 'text-emerald-600' : 'text-rose-600'" x-text="currentStock > 0 ? 'In Stock' : 'Out of Stock'">{{ $product->stock_quantity > 0 ? 'In Stock' : 'Out of Stock' }}</span>
                    @endif
                    <span class="rounded-lg bg-slate-100 px-2.5 py-1.5">SKU: <strong class="font-normal text-slate-500" x-text="currentSku || 'N/A'">{{ $product->sku ?: 'N/A' }}</strong></span>
                    @if ($product->brand)
                        <span class="rounded-lg bg-slate-100 px-2.5 py-1.5">Brand: <strong class="font-normal text-slate-500">{{ $product->brand->name }}</strong></span>
                    @endif
                </div>
                @if ($product->short_description)<p class="mt-4 text-base leading-7 text-slate-600">{{ $product->short_description }}</p>@endif
                <template x-for="group in optionGroups" :key="group.name">
                    <div class="mt-5">
                        <p class="text-sm font-semibold text-slate-800" x-text="group.name + ':'"></p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <template x-for="value in group.values" :key="value">
                                <button type="button" @click="selectOption(group.name, value)" class="rounded-xl border px-4 py-2 text-sm font-medium transition" :class="selections[group.name] === value ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-300 text-slate-700 hover:border-slate-400'" x-text="value"></button>
                            </template>
                        </div>
                    </div>
                </template>
                <template x-if="hasVariants && ! selectedVariant">
                    <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-700">Please select {{ $optionGroups->pluck('name')->implode(' and ') }} to continue.</p>
                </template>
                <template x-if="! hasVariants || selectedVariant">
                <div>
                <div x-show="currentStock > 0" x-cloak><form x-ref="addToCartForm" method="POST" action="{{ route('cart.store', $product) }}" @submit.prevent="addToCart()" class="mt-5 rounded-2xl border border-slate-200 bg-transparent p-4 shadow-sm">@csrf<input type="hidden" name="variant_id" :value="selectedVariant?.id ?? ''"><div class="flex items-center justify-between gap-4"><label for="product-quantity" class="text-sm font-medium text-slate-700">Quantity:</label><div class="flex h-11 items-center rounded-xl border border-slate-300 bg-white p-1"><button type="button" @click="quantity = Math.max(1, quantity - 1)" :disabled="quantity <= 1" aria-label="Decrease quantity" class="grid h-8 w-8 place-items-center rounded-full bg-slate-100 text-xl font-light leading-none text-slate-500 transition hover:bg-slate-200 disabled:opacity-40">−</button><input id="product-quantity" name="quantity" type="number" x-model.number="quantity" min="1" :max="currentStock" class="h-8 w-16 border-0 p-0 text-center text-base font-medium text-slate-700 focus:ring-0"><button type="button" @click="quantity = Math.min(currentStock, quantity + 1)" :disabled="quantity >= currentStock" aria-label="Increase quantity" class="grid h-8 w-8 place-items-center rounded-full bg-slate-100 text-xl font-light leading-none text-slate-500 transition hover:bg-slate-200 disabled:opacity-40">+</button></div></div><div class="mt-3 grid grid-cols-2 gap-3"><button type="submit" :disabled="adding" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-indigo-200 px-2 py-2 text-xs font-semibold text-indigo-600 transition duration-200 hover:-translate-y-0.5 hover:border-indigo-400 hover:bg-indigo-50 hover:shadow-md disabled:cursor-wait disabled:opacity-60 sm:px-4 sm:text-sm" :class="added && 'border-emerald-300 text-emerald-600'" style="background-color: transparent"><span x-text="adding ? 'Adding...' : (added ? 'Added to cart ✓' : 'কার্টে যোগ করুন')">কার্টে যোগ করুন</span></button><button type="button" @click="$dispatch('open-modal', 'direct-order')" class="inline-flex min-h-11 items-center justify-center rounded-xl border px-2 py-2 text-xs font-semibold transition duration-200 hover:-translate-y-0.5 hover:bg-rose-50 hover:shadow-md sm:px-4 sm:text-sm" style="border-color: #fda4af; background-color: transparent; color: #e11d48">অর্ডার করুন</button><a href="https://wa.me/8801736741793?text={{ urlencode('আমি '.$product->title.' অর্ডার করতে চাই। '.$productUrl) }}" target="_blank" rel="noopener" class="inline-flex min-h-11 items-center justify-center rounded-xl border px-2 py-2 text-center text-xs font-semibold transition duration-200 hover:-translate-y-0.5 hover:bg-emerald-50 hover:shadow-md sm:px-4 sm:text-sm" style="border-color: #b7efca; background-color: transparent; color: #128C3E">হোয়াটসঅ্যাপে অর্ডার করুন</a><a href="tel:+8801736741793" class="inline-flex min-h-11 items-center justify-center rounded-xl border px-2 py-2 text-center text-xs font-semibold transition duration-200 hover:-translate-y-0.5 hover:bg-blue-50 hover:shadow-md sm:px-4 sm:text-sm" style="border-color: #93c5fd; background-color: transparent; color: #2563eb">কল অর্ডার: 01736741793</a></div></form></div>
                <div x-show="currentStock <= 0" x-cloak class="mt-5 rounded-xl bg-rose-50 px-4 py-3 font-bold text-rose-700">Currently out of stock</div>
                </div>
                </template>
                <div x-data="{ saved: localStorage.getItem('saved-product-{{ $product->id }}') === '1', compared: localStorage.getItem('compare-product-{{ $product->id }}') === '1', copied: false }" class="mt-2 flex flex-wrap items-center justify-between gap-4 rounded-2xl bg-transparent px-4 py-2 shadow-[0_1px_2px_rgba(15,23,42,.04)] sm:px-5">
                    <div class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                        <span>Share:</span>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($productUrl) }}" target="_blank" rel="noopener" aria-label="Share on Facebook" class="grid h-8 w-8 place-items-center rounded-full border border-slate-300 bg-white text-xs font-black text-slate-700 transition hover:border-blue-600 hover:text-blue-600">f</a>
                        <a href="https://wa.me/?text={{ urlencode($product->title.' '.$productUrl) }}" target="_blank" rel="noopener" aria-label="Share on WhatsApp" class="grid h-8 w-8 place-items-center rounded-full border border-slate-300 bg-white text-slate-700 transition hover:border-emerald-600 hover:text-emerald-600"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.6 0 .3 5.3.3 11.8c0 2.1.5 4.1 1.6 5.9L.2 24l6.5-1.7a11.8 11.8 0 0 0 5.4 1.4h.1C18.7 23.7 24 18.4 24 11.9c0-3.2-1.2-6.1-3.5-8.4Zm-8.4 18.2c-1.7 0-3.4-.5-4.9-1.3l-.4-.2-3.8 1 1-3.7-.2-.4a9.8 9.8 0 1 1 8.3 4.6Zm5.4-7.3c-.3-.2-1.8-.9-2.1-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-.9 1.2-.2.2-.4.2-.7.1-1.8-.9-3-1.6-4.2-3.6-.3-.6.3-.5.9-1.8.1-.2 0-.4 0-.6l-1-2.4c-.3-.6-.6-.5-.9-.5h-.7c-.2 0-.6.1-.9.4-1 1-.1 3.5 1 5 1.4 2.1 3.4 3.7 5.7 4.7 2.1.9 2.9 1 4 .8.7-.1 1.8-.7 2-1.4.3-.7.3-1.3.2-1.4-.2-.1-.4-.2-.7-.4Z"/></svg></a>
                        <span class="relative"><button type="button" @click="copied = true; setTimeout(() => copied = false, 1600); if (navigator.clipboard) { navigator.clipboard.writeText('{{ $productUrl }}') } else { const input = document.createElement('textarea'); input.value = '{{ $productUrl }}'; document.body.appendChild(input); input.select(); document.execCommand('copy'); input.remove() }" aria-label="Copy product link" class="grid h-8 w-8 place-items-center rounded-full border border-slate-300 bg-white text-slate-600 transition hover:border-indigo-400 hover:text-indigo-600"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M9.5 14.5 14.5 9M7.2 16.8l-1 1a3.5 3.5 0 0 1-5-5l3.6-3.6a3.5 3.5 0 0 1 5 0M16.8 7.2l1-1a3.5 3.5 0 0 1 5 5l-3.6 3.6a3.5 3.5 0 0 1-5 0"/></svg></button><span x-cloak x-show="copied" x-transition.opacity class="absolute left-1/2 top-full z-10 mt-2 -translate-x-1/2 whitespace-nowrap rounded-md bg-slate-900 px-2 py-1 text-[10px] font-medium text-white shadow-sm">Copied</span></span>
                    </div>
                    <div class="flex shrink-0 items-center gap-4 text-sm font-medium text-slate-900">
                        <button type="button" @click="saved = !saved; localStorage.setItem('saved-product-{{ $product->id }}', saved ? '1' : '0')" class="flex items-center gap-2 whitespace-nowrap transition hover:text-indigo-600" :class="saved && 'text-indigo-600'"><svg class="h-5 w-5" :fill="saved ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 4.75A1.75 1.75 0 0 1 7.75 3h8.5A1.75 1.75 0 0 1 18 4.75V21l-6-3.75L6 21V4.75Z"/></svg><span x-text="saved ? 'Saved' : 'Save'"></span></button>
                        <button type="button" @click="compared = !compared; localStorage.setItem('compare-product-{{ $product->id }}', compared ? '1' : '0')" class="flex items-center gap-2 whitespace-nowrap transition hover:text-indigo-600" :class="compared && 'text-indigo-600'"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 7h12M8 12h12M8 17h12M4 7h.01M4 12h.01M4 17h.01"/></svg><span x-text="compared ? 'Added to Compare' : 'Add to Compare'"></span></button>
                    </div>
                </div>
                <div class="mt-3 grid grid-cols-3 gap-3 text-center text-xs font-normal text-slate-500"><div class="flex flex-col items-center justify-center gap-2 rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200/70"><span class="grid h-9 w-9 place-items-center rounded-full bg-slate-50 text-slate-400"><svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2" stroke-width="1.5"/><path stroke-linecap="round" stroke-width="1.5" d="M8 10V7a4 4 0 0 1 8 0v3m-6 5 1.5 1.5L15 13"/></svg></span><span>Secure ordering</span></div><div class="flex flex-col items-center justify-center gap-2 rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200/70"><span class="grid h-9 w-9 place-items-center rounded-full bg-amber-50/50 text-amber-400"><svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7h10v10H4V7Zm10 3h3l3 3v4h-6v-7ZM8 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm9 0a2 2 0 1 0 0-4 2 2 0 1 0 0 4Z"/></svg></span><span>COD available</span></div><div class="flex flex-col items-center justify-center gap-2 rounded-xl bg-white p-3 shadow-sm ring-1 ring-slate-200/70"><span class="grid h-9 w-9 place-items-center rounded-full bg-emerald-50/50 text-emerald-400"><svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m12 3 2.4 2.1 3.2-.2.5 3.2 2.3 2.2-1.7 2.7.7 3.1-3 1.1-1.5 2.8-2.9-1.4L9.1 20l-1.5-2.8-3-1.1.7-3.1-1.7-2.7 2.3-2.2.5-3.2 3.2.2L12 3Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m9 11.5 2 2 4-4"/></svg></span><span>Quality checked</span></div></div>
            </div>
        </div>
        <section class="mt-8 sm:mt-10 lg:mt-12">
            <nav class="grid grid-cols-2 gap-2 sm:flex" aria-label="Product information sections">
                <a href="#product-specification" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-center text-sm font-semibold text-slate-950 transition hover:border-slate-300 hover:bg-slate-50 sm:shrink-0 sm:px-5">Specification</a>
                <a href="#product-description" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-center text-sm font-semibold text-slate-950 transition hover:border-slate-300 hover:bg-slate-50 sm:shrink-0 sm:px-5">Description</a>
                <a href="#product-questions" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-center text-sm font-semibold text-slate-950 transition hover:border-slate-300 hover:bg-slate-50 sm:shrink-0 sm:px-5">Questions</a>
                <a href="#product-reviews" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-center text-sm font-semibold text-slate-950 transition hover:border-slate-300 hover:bg-slate-50 sm:shrink-0 sm:px-5">Reviews</a>
            </nav>
            <div class="mt-3 space-y-4">
                <article id="product-specification" class="scroll-mt-28 rounded-3xl border border-slate-200 bg-white p-5 sm:p-7">
                    <h2 class="mb-4 text-2xl font-normal text-slate-950">Specification</h2>
                    @if ($productSpecifications->isNotEmpty())
                        <div class="space-y-4">
                            @foreach ($productSpecifications as $section)
                                <section>
                                    <h3 class="rounded-lg bg-indigo-50/70 px-5 py-3 text-sm font-bold text-indigo-700">{{ $section['title'] }}</h3>
                                    <dl class="text-sm">
                                        @foreach ($section['items'] as $item)
                                            <div class="grid grid-cols-[minmax(120px,250px)_minmax(0,1fr)] gap-3 border-b border-slate-200 px-4 py-3 last:border-b-0"><dt class="text-slate-600">{{ $item['title'] }}</dt><dd class="font-semibold text-slate-950">{{ $item['value'] }}</dd></div>
                                        @endforeach
                                    </dl>
                                </section>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-slate-500">No specifications are available for this product.</p>
                    @endif
                </article>
                <article id="product-description" class="scroll-mt-28 rounded-3xl border border-slate-200 bg-white p-5 sm:p-7">
                    <h2 class="mb-4 text-2xl font-normal text-slate-950">Description</h2>
                    @if ($product->description)<div class="prose prose-slate max-w-none leading-7">{!! $product->description !!}</div>@else<p class="text-sm text-slate-500">No description is available for this product.</p>@endif
                </article>
                <article id="product-questions" class="scroll-mt-28 rounded-3xl border border-slate-200 bg-white p-5 sm:p-7">
                    <h2 class="mb-4 text-2xl font-normal text-slate-950">Questions</h2>
                    @forelse($product->questions ?? [] as $question)
                        @if($loop->first)
                            <details open class="group border-b border-slate-200 py-4 first:pt-0 last:border-b-0 last:pb-0">
                        @else
                            <details class="group border-b border-slate-200 py-4 first:pt-0 last:border-b-0 last:pb-0">
                        @endif
                            <summary class="flex cursor-pointer list-none items-start justify-between gap-4 text-left [&::-webkit-details-marker]:hidden">
                                <span class="grid min-w-0 grid-cols-[24px_minmax(0,1fr)] gap-2 text-sm font-bold leading-6 text-slate-950"><span class="text-indigo-600">Q:</span><span>{{ $question['question'] }}</span></span>
                                <svg class="mt-1 h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m6 9 6 6 6-6"/></svg>
                            </summary>
                            <div class="mt-4 grid grid-cols-[24px_minmax(0,1fr)] gap-2 text-sm leading-7 text-slate-600"><strong class="font-bold text-emerald-700">A:</strong><p>{{ $question['answer'] }}</p></div>
                        </details>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center"><p class="text-sm font-semibold text-slate-700">No questions are available for this product yet.</p><p class="mt-1 text-xs text-slate-500">Please contact us if you need more information.</p></div>
                    @endforelse
                </article>
                <article id="product-reviews" x-data="{ reviewImage: null }" class="scroll-mt-28 rounded-3xl border border-slate-200 bg-white p-5 sm:p-7">
                    <h2 class="text-2xl font-normal text-slate-950">Reviews</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Get specific details about this product from customers who own it.</p>
                    <div class="mt-4 flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="inline-flex items-center gap-4 rounded-2xl border border-amber-200 bg-amber-50/70 px-4 py-3">
                            <div class="text-center"><strong class="block text-4xl font-black leading-none text-slate-950">{{ $product->reviews_count ? number_format((float) $product->reviews_avg_rating, 1) : '0' }}</strong><span class="mt-1 block text-[11px] font-medium text-slate-500">out of 5</span></div>
                            <span class="h-10 w-px bg-amber-200"></span>
                            <div><span class="flex gap-1 text-xl leading-none" aria-label="{{ number_format((float) $product->reviews_avg_rating, 1) }} out of 5 stars">@foreach(range(1, 5) as $star)<span class="{{ $star <= round((float) $product->reviews_avg_rating) ? 'text-amber-400' : 'text-slate-300' }}">&#9733;</span>@endforeach</span><span class="sr-only">Maximum rating is 5 out of 5 stars.</span><span class="mt-1.5 block text-xs font-bold text-amber-700">{{ $product->reviews_count }} {{ Str::plural('review', $product->reviews_count) }}</span></div>
                        </div>
                        @guest
                            <a href="{{ route('login') }}" class="inline-flex min-h-11 w-fit items-center justify-center rounded-xl border border-indigo-200 px-5 py-2.5 text-sm font-bold text-indigo-600 transition hover:border-indigo-500 hover:bg-indigo-50">Write a Review</a>
                        @elseif($canReview)
                            <a href="{{ route('customer.reviews.index') }}#product-{{ $product->id }}" class="inline-flex min-h-11 w-fit items-center justify-center rounded-xl border border-indigo-200 px-5 py-2.5 text-sm font-bold text-indigo-600 transition hover:border-indigo-500 hover:bg-indigo-50">Write or Edit Review</a>
                        @else
                            <span class="rounded-xl bg-slate-100 px-4 py-3 text-xs font-semibold text-slate-500">Completed purchase required to review</span>
                        @endguest
                    </div>
                    <div class="divide-y divide-slate-200">
                        @forelse($reviews as $review)
                            <div class="py-5 last:pb-0">
                                <div class="flex gap-1 text-lg leading-none" aria-label="{{ $review->rating }} out of 5 stars">@foreach(range(1, 5) as $star)<span class="{{ $star <= $review->rating ? 'text-amber-400' : 'text-slate-300' }}">&#9733;</span>@endforeach</div>
                                <p class="mt-4 max-w-4xl whitespace-pre-line text-sm leading-7 text-slate-700">{{ $review->body }}</p>
                                @if(filled($review->image_paths))
                                    <div class="mt-4 flex flex-wrap gap-3">@foreach($review->image_paths as $path)<button type="button" @click="reviewImage = @js(asset('storage/'.$path))" class="h-20 w-20 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 transition hover:border-blue-400"><img src="{{ asset('storage/'.$path) }}" alt="Review image by {{ $review->user->name }}" class="h-full w-full object-cover"></button>@endforeach</div>
                                @endif
                                <p class="mt-4 text-sm text-slate-500">By <strong class="font-bold text-slate-950">{{ $review->user->name }}</strong> on {{ $review->created_at->format('d M Y') }} <span class="ml-2 rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700">Verified purchase</span></p>
                            </div>
                        @empty
                            <div class="py-7 text-center"><p class="text-sm font-semibold text-slate-700">No reviews for this product yet.</p><p class="mt-1 text-xs text-slate-500">Be the first verified customer to share an experience.</p></div>
                        @endforelse
                    </div>
                    <div x-show="reviewImage" x-cloak x-transition.opacity @keydown.escape.window="reviewImage = null" class="fixed inset-0 z-[80] grid place-items-center bg-slate-950/85 p-4" role="dialog" aria-modal="true" aria-label="Review image preview">
                        <button type="button" @click="reviewImage = null" class="absolute right-5 top-5 grid h-11 w-11 place-items-center rounded-full bg-white text-2xl text-slate-700" aria-label="Close image preview">&times;</button>
                        <img :src="reviewImage" alt="Full review image" class="max-h-[88vh] max-w-[92vw] rounded-2xl object-contain shadow-2xl">
                    </div>
                </article>
            </div>
        </section>
        @if ($relatedProducts->isNotEmpty())<section class="mt-8 sm:mt-10 lg:mt-12"><div class="mb-5 sm:mb-6"><div class="mb-3 h-1 w-12 rounded-full bg-orange-500"></div><p class="text-xs font-bold uppercase tracking-[.18em] text-orange-600">Recommended for you</p><h2 class="mt-1 text-2xl font-normal tracking-tight text-slate-950 sm:text-3xl">You may also like</h2></div><div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5 lg:gap-5">@foreach ($relatedProducts as $relatedProduct)<x-store-product-card :product="$relatedProduct" />@endforeach</div></section>@endif
    </div>

    <x-modal name="direct-order" :show="$errors->any()" maxWidth="2xl" focusable>
        <form method="POST" action="{{ route('direct-order.store', $product) }}" x-data="{ quantity: {{ max(1, (int) old('quantity', 1)) }}, maximum: {{ $product->stock_quantity }}, unitPrice: {{ $product->current_price }}, isDigital: {{ $product->isDigital() ? 'true' : 'false' }}, deliveryArea: @js(old('delivery_area', 'dhaka_city')), charges: @js($deliveryRates), money(value) { return new Intl.NumberFormat('en-BD', { maximumFractionDigits: 0 }).format(value) }, get subtotal() { return this.unitPrice * this.quantity }, get shipping() { return this.isDigital ? 0 : this.charges[this.deliveryArea] }, get total() { return this.subtotal + this.shipping } }" class="bg-slate-50 text-slate-950">
            @csrf
            <input type="hidden" name="payment_method" value="cash_on_delivery">
            <header class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4 sm:px-7">
                <h2 class="text-lg font-bold">অর্ডার করুন - ক্যাশ অন ডেলিভারিতে</h2>
                <x-modal-close-button x-on:click="$dispatch('close')" />
            </header>

            <div class="max-h-[calc(100vh-7rem)] overflow-y-auto p-4 sm:p-7">
                @if ($errors->any())<div class="mb-5 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-700">অনুগ্রহ করে প্রয়োজনীয় তথ্যগুলো সঠিকভাবে পূরণ করুন।</div>@endif

                <div class="flex items-center gap-4 border-b border-slate-200 bg-white px-4 py-4">
                    <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-slate-100">@if ($product->featured_image_path)<img src="{{ asset('storage/'.$product->featured_image_path) }}" alt="" class="h-full w-full object-cover">@endif</div>
                    <div class="min-w-0 flex-1"><p class="line-clamp-2 text-sm font-semibold">{{ $product->title }}</p><p class="mt-1 text-sm text-slate-500"><span class="currency-symbol">৳</span>{{ number_format($product->current_price, 0) }}</p></div>
                    <div class="flex h-10 shrink-0 items-center rounded-lg border border-slate-300 bg-white p-1"><button type="button" @click="quantity = Math.max(1, quantity - 1)" class="grid h-8 w-8 place-items-center rounded-md text-lg hover:bg-slate-100">−</button><input name="quantity" x-model.number="quantity" type="number" min="1" :max="maximum" class="h-8 w-10 border-0 p-0 text-center text-sm focus:ring-0"><button type="button" @click="quantity = Math.min(maximum, quantity + 1)" class="grid h-8 w-8 place-items-center rounded-md text-lg hover:bg-slate-100">+</button></div>
                </div>

                <fieldset x-show="! isDigital" class="mt-5"><legend class="mb-3 text-base font-bold">ডেলিভারি</legend><div class="space-y-2">
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border bg-white px-4 py-3.5 text-sm transition" :class="deliveryArea === 'dhaka_city' ? 'border-blue-500 bg-blue-50/60 ring-1 ring-blue-500' : 'border-slate-300 hover:border-slate-400'"><input type="radio" name="delivery_area" value="dhaka_city" x-model="deliveryArea" :required="! isDigital" class="text-blue-600 focus:ring-blue-500"><span class="flex-1 font-semibold">ঢাকা সিটির ভেতরে</span><strong>{{ number_format($deliveryRates['dhaka_city'], 2) }} TK</strong></label>
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border bg-white px-4 py-3.5 text-sm transition" :class="deliveryArea === 'dhaka_outside' ? 'border-blue-500 bg-blue-50/60 ring-1 ring-blue-500' : 'border-slate-300 hover:border-slate-400'"><input type="radio" name="delivery_area" value="dhaka_outside" x-model="deliveryArea" :required="! isDigital" class="text-blue-600 focus:ring-blue-500"><span class="flex-1 font-semibold">ঢাকা সিটির বাইরে</span><strong>{{ number_format($deliveryRates['dhaka_outside'], 2) }} TK</strong></label>
                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border bg-white px-4 py-3.5 text-sm transition" :class="deliveryArea === 'outside_dhaka' ? 'border-blue-500 bg-blue-50/60 ring-1 ring-blue-500' : 'border-slate-300 hover:border-slate-400'"><input type="radio" name="delivery_area" value="outside_dhaka" x-model="deliveryArea" :required="! isDigital" class="text-blue-600 focus:ring-blue-500"><span class="flex-1 font-semibold">ঢাকা জেলার বাইরে</span><strong>{{ number_format($deliveryRates['outside_dhaka'], 2) }} TK</strong></label>
                </div></fieldset>

                <div class="mt-5 overflow-hidden rounded-xl bg-slate-200/60 text-sm">
                    <div class="flex justify-between gap-4 px-4 py-2.5"><span>মোট</span><strong><span x-text="money(subtotal)"></span> TK</strong></div>
                    <div class="flex justify-between gap-4 border-t border-slate-300 px-4 py-2.5"><span>ডেলিভারি চার্জ</span><strong><span x-text="money(shipping)"></span> TK</strong></div>
                    <div class="flex justify-between gap-4 border-t border-slate-300 px-4 py-3 font-bold"><span>সর্বমোট</span><strong><span x-text="money(total)"></span> TK</strong></div>
                </div>

                <h3 class="mt-7 text-center text-xl font-bold">অর্ডার করতে নিচের তথ্যগুলি দিন</h3>
                <div class="mt-5 grid gap-4">
                    <label class="grid gap-2 text-sm font-bold sm:grid-cols-[130px_minmax(0,1fr)] sm:items-center"><span>নাম</span><input name="customer_name" value="{{ old('customer_name') }}" required class="rounded-lg border-slate-300 text-sm" placeholder="আপনার নাম"></label>
                    <label class="grid gap-2 text-sm font-bold sm:grid-cols-[130px_minmax(0,1fr)] sm:items-center"><span>মোবাইল নাম্বার</span><input name="customer_phone" value="{{ old('customer_phone') }}" required class="rounded-lg border-slate-300 text-sm" placeholder="১১ ডিজিট মোবাইল নাম্বার"></label>
                    <label x-show="! isDigital" class="grid gap-2 text-sm font-bold sm:grid-cols-[130px_minmax(0,1fr)] sm:items-start"><span class="sm:pt-3">ঠিকানা</span><textarea name="shipping_address" :required="! isDigital" rows="2" class="rounded-lg border-slate-300 text-sm" placeholder="বাসা নম্বর, গ্রাম/মহল্লা, উপজেলা, জেলা">{{ old('shipping_address') }}</textarea></label>
                    <label class="grid gap-2 text-sm font-bold sm:grid-cols-[130px_minmax(0,1fr)] sm:items-center"><span>ইমেইল <small class="block font-normal text-slate-500">(ঐচ্ছিক)</small></span><input name="customer_email" type="email" value="{{ old('customer_email') }}" class="rounded-lg border-slate-300 text-sm" placeholder="আপনার ইমেইল (অপশনাল)"></label>
                    <label class="grid gap-2 text-sm font-bold sm:grid-cols-[130px_minmax(0,1fr)] sm:items-center"><span>অর্ডার নোট</span><input name="customer_note" value="{{ old('customer_note') }}" class="rounded-lg border-slate-300 text-sm" placeholder="কোনো কিছু বলতে চাইলে লিখুন (অপশনাল)"></label>
                </div>

                <button type="submit" class="mt-6 w-full rounded-lg bg-[#ff4f2d] px-5 py-3.5 text-sm font-bold text-white transition hover:bg-[#ed3f1f]">অর্ডার কনফার্ম করুন <span x-text="money(total)"></span> TK</button>
                <p class="mt-3 text-center text-xs text-slate-500">আমাদের একজন কাস্টমার প্রতিনিধি আপনাকে কল করে অর্ডার কনফার্ম করবে</p>
            </div>
        </form>
    </x-modal>
</x-storefront-layout>
