@php
    $catalogItem = $activeCategory ?? $activeBrand;
    $catalogPageTitle = $catalogItem?->name;
    $categoryBreadcrumbs = collect();
    $breadcrumbCategory = $activeCategory;
    while ($breadcrumbCategory) {
        $categoryBreadcrumbs->prepend($breadcrumbCategory);
        $breadcrumbCategory = $breadcrumbCategory->parentRecursive;
    }
    $catalogDescriptionSource = $activeCategory?->description ?: $activeBrand?->description;
    $catalogDescription = $catalogDescriptionSource
        ? str(strip_tags($catalogDescriptionSource))->squish()->limit(160)
        : ($activeBrand ? 'Shop '.$activeBrand->name.' products from '.($websiteSettings?->site_name ?? config('app.name')).'.' : $websiteSettings?->meta_description);
    $bottomCatalogDescription = $activeCategory
        ? $activeCategory->extra_description
        : $activeBrand?->extra_description;
    $missingCatalogDescription = $catalogItem && blank($bottomCatalogDescription);
    $breadcrumbSchema = $activeCategory ? [
        '@'.'context' => 'https://schema.org',
        '@'.'type' => 'BreadcrumbList',
        'itemListElement' => collect([['name' => 'Home', 'url' => route('storefront.index')]])
            ->concat($categoryBreadcrumbs->map(fn ($category) => ['name' => $category->name, 'url' => route('catalog.show', $category->slug)]))
            ->values()
            ->map(fn ($item, $index) => [
                '@'.'type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ])->all(),
    ] : null;
@endphp
<x-storefront-layout :title="$catalogPageTitle ?: $websiteSettings?->seo_title" :description="$catalogDescription" :canonical="$catalogItem ? route('catalog.show', $catalogItem->slug) : route('storefront.index')" :include-site-name="! $catalogItem">
    @php
        $heroSlides = collect($heroSettings?->hero_slider_paths ?? [])->map(fn ($path) => asset('storage/'.$path))->values();
        if ($heroSlides->isEmpty()) {
            $heroSlides = collect([$heroProduct?->featured_image_path ? asset('storage/'.$heroProduct->featured_image_path) : asset('images/hero-placeholder.svg')]);
        }
        $heroSideImages = collect([
            $heroSettings?->hero_side_image_one_path,
            $heroSettings?->hero_side_image_two_path,
        ])->map(fn ($path) => $path ? asset('storage/'.$path) : null);
    @endphp
    @if ($catalogItem)
        <x-catalog-hero
            :catalog-item="$catalogItem"
            :active-category="$activeCategory"
            :category-breadcrumbs="$categoryBreadcrumbs"
            :description-source="$catalogDescriptionSource"
            :description="$catalogDescription"
            :catalog-links="$catalogLinks"
            :product-count="$catalogProductCount"
            :flash-sale-settings="$websiteSettings?->flash_sale_settings ?? []"
        />
        @if ($activeCategory)
            <script type="application/ld+json">{!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endif
    @else
    <section class="overflow-visible bg-[#f8fafc] text-[#111111]">
        <div class="mx-auto grid max-w-[1400px] gap-4 px-4 pt-[25px] sm:px-6 lg:grid-cols-12 lg:px-8">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-700 to-indigo-900 p-6 text-white shadow-sm sm:p-8 lg:col-span-8 lg:min-h-[560px] lg:p-10">
                <div class="absolute -right-20 -top-24 h-72 w-72 rounded-full bg-indigo-400/20 blur-3xl"></div>
                <div class="relative grid h-full items-center gap-8 md:grid-cols-[1.05fr_.95fr]">
                    <div>
                        <span class="inline-flex rounded-full border border-white/25 bg-white/10 px-3 py-1.5 text-[10px] font-semibold uppercase tracking-[.18em] sm:text-xs">Bangladesh's online marketplace</span>
                        <h1 class="mt-5 text-3xl font-semibold leading-[1.08] tracking-tight sm:text-4xl lg:text-5xl">{{ $heroSettings?->hero_title ?: 'Everything you need, delivered with ease.' }}</h1>
                        <p class="mt-4 text-sm leading-6 text-indigo-100 sm:text-base">{{ $heroSettings?->hero_subtitle ?: 'Discover trusted products from verified sellers and enjoy a simpler, safer way to shop online.' }}</p>
                        <div class="mt-6 flex flex-wrap gap-4 text-xs font-medium sm:text-sm">@foreach (['Cash on Delivery', 'Verified Sellers', 'Easy Returns'] as $trustPoint)<span class="inline-flex items-center gap-2"><svg class="h-4 w-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6"/></svg>{{ $trustPoint }}</span>@endforeach</div>
                        <div class="mt-7 flex flex-wrap gap-3"><a href="#latest-products" class="rounded-full bg-white px-5 py-3 text-sm font-semibold text-indigo-700 shadow-sm">Shop Now →</a><a href="#shop-categories" class="rounded-full border border-white/50 px-5 py-3 text-sm font-semibold">Browse Categories</a></div>
                    </div>
                    <article x-data="{ slides: @js($heroSlides), active: 0, init() { if (this.slides.length > 1) setInterval(() => this.next(), 5000) }, next() { this.active = (this.active + 1) % this.slides.length }, previous() { this.active = (this.active - 1 + this.slides.length) % this.slides.length } }" data-featured-product-id="{{ $heroProduct?->id }}" class="rounded-2xl bg-white p-3 text-slate-900 shadow-xl sm:p-4">
                        <div class="relative aspect-[4/3] overflow-hidden rounded-xl bg-[#eeeeec]">
                            <template x-for="(slide, index) in slides" :key="slide"><img x-show="active === index" x-transition.opacity :src="slide" alt="Hero slide" class="absolute inset-0 h-full w-full object-cover"></template>
                            <span class="absolute left-3 top-3 rounded-full bg-indigo-700 px-3 py-1 text-[11px] font-semibold text-white">@if ($heroProduct?->sale_price && $heroProduct?->price > $heroProduct?->sale_price){{ round((1 - ($heroProduct->sale_price / $heroProduct->price)) * 100) }}% OFF @else FEATURED @endif</span>
                            @if ($heroSlides->count() > 1)<button type="button" @click="previous()" aria-label="Previous hero image" class="absolute left-3 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-white/90 shadow">←</button><button type="button" @click="next()" aria-label="Next hero image" class="absolute right-3 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-white/90 shadow">→</button>@endif
                        </div>
                        <div class="px-1 pt-4"><p class="text-[10px] font-semibold uppercase tracking-[.16em] text-slate-500">{{ $heroProduct?->brand?->name ?? $heroProduct?->categories->first()?->name ?? 'Marketplace pick' }}</p><a href="{{ $heroProduct ? route('catalog.show', $heroProduct->slug) : '#latest-products' }}" class="mt-1.5 block font-semibold hover:text-indigo-700">{{ $heroProduct?->title ?? 'Explore today’s top picks' }}</a><div class="mt-3 flex items-end justify-between"><div>@if ($heroProduct)<b class="text-xl">৳{{ number_format($heroProduct->current_price, 2) }}</b>@if ($heroProduct->sale_price && $heroProduct->price > $heroProduct->sale_price)<span class="ml-1 text-xs text-slate-400 line-through">৳{{ number_format((float) $heroProduct->price, 2) }}</span>@endif @endif</div><span class="flex gap-1.5" aria-label="Carousel position"><template x-for="(_, index) in slides"><button type="button" @click="active=index" :aria-label="`Show hero image ${index + 1}`" class="h-1.5 rounded-full" :class="active === index ? 'w-5 bg-indigo-700' : 'w-1.5 bg-slate-300'"></button></template></span></div></div>
                    </article>
                </div>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:col-span-4 lg:grid-cols-1">
                @foreach ([['Pay your way, securely', 'Choose Cash on Delivery or secure online payment.', 'Start shopping', '#latest-products'], ['Fast delivery nationwide', 'Reliable doorstep delivery across Bangladesh.', 'Track an order', route('storefront.orders.track')]] as $promoIndex => $promo)
                    <article class="hero-promo-animated-bg group relative flex min-h-[245px] overflow-hidden rounded-2xl bg-gradient-to-br {{ $promoIndex ? 'from-indigo-600 via-indigo-700 to-violet-800' : 'from-orange-400 via-amber-500 to-rose-500' }} p-6 text-white shadow-sm transition duration-500 hover:-translate-y-1 hover:shadow-xl sm:p-7 lg:min-h-0">
                        <span class="absolute -right-8 -top-12 h-40 w-40 animate-pulse rounded-full bg-white/20 blur-2xl"></span>
                        <span class="absolute -bottom-14 left-1/3 h-32 w-32 animate-pulse rounded-full bg-white/10 blur-2xl [animation-delay:700ms]"></span>
                        @if ($heroSideImages->get($promoIndex))<img src="{{ $heroSideImages->get($promoIndex) }}" alt="Hero side image {{ $promoIndex + 1 }}" class="absolute inset-y-0 right-0 h-full w-2/5 object-cover opacity-20 transition duration-700 group-hover:scale-110">@endif
                        <div class="relative flex max-w-[78%] flex-col items-start">
                            <span class="grid h-11 w-11 animate-bounce place-items-center rounded-full bg-white/95 text-indigo-700 shadow-lg [animation-duration:2.8s] motion-reduce:animate-none"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h18M5 5h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm2 9h4"/></svg></span>
                            <h2 class="mt-4 text-xl font-semibold">{{ $promo[0] }}</h2><p class="mt-2 text-sm leading-6 text-white/85">{{ $promo[1] }}</p><a href="{{ $promo[3] }}" class="mt-auto rounded-full bg-white/95 px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition duration-300 hover:bg-white hover:shadow-md">{{ $promo[2] }} →</a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>
    @endif
    @if (! $catalogItem && ($heroSettings?->service_marquee_enabled ?? true))
        @php
            $defaultServiceMarqueeItems = [
                ['label' => 'অন্যান্য দোকানের তুলনায় কম দাম', 'icon' => 'price'],
                ['label' => '১০০০ টাকা থেকে ফ্রি ডেলিভারি', 'icon' => 'delivery'],
                ['label' => 'প্রিমিয়াম পণ্য', 'icon' => 'quality'],
                ['label' => 'যেকোনো ব্যাংকের কার্ড দিয়ে নিরাপদ পেমেন্ট', 'icon' => 'payment'],
                ['label' => '২৪/৭ সাপোর্ট সবসময় আপনার পাশে', 'icon' => 'support'],
            ];
            $serviceMarqueeItems = $heroSettings?->service_marquee_items ?: $defaultServiceMarqueeItems;
            $serviceHighlightStyles = [
                ['color' => 'text-orange-300', 'icon' => 'price'],
                ['color' => 'text-sky-300', 'icon' => 'delivery'],
                ['color' => 'text-lime-300', 'icon' => 'quality'],
                ['color' => 'text-violet-300', 'icon' => 'payment'],
                ['color' => 'text-indigo-300', 'icon' => 'support'],
            ];
            $serviceHighlights = collect($serviceMarqueeItems)->values()->map(fn ($item, $index) => [
                'label' => is_array($item) ? $item['label'] : $item,
                'icon' => is_array($item) ? $item['icon'] : $serviceHighlightStyles[$index]['icon'],
                'color' => $serviceHighlightStyles[$index]['color'],
            ]);
        @endphp
        <section aria-label="Service highlights" class="bg-[#f8fafc] py-[25px] dark:bg-[#111827]">
            <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8">
                <div class="overflow-hidden rounded-xl bg-white py-5 shadow-sm">
                    <div class="storefront-marquee flex w-max" style="animation-duration: {{ (int) ($heroSettings?->service_marquee_speed ?? 28) }}s">
                        @foreach (range(1, 2) as $copy)
                            <div class="flex shrink-0 items-center" @if ($copy === 2) aria-hidden="true" @endif>
                                @foreach ($serviceHighlights as $highlight)
                                    <div class="flex min-w-[290px] shrink-0 items-center justify-center gap-3 px-8 text-sm font-semibold text-slate-600 sm:min-w-[340px]">
                                        <span class="{{ $highlight['color'] }}">
                                            @switch($highlight['icon'])
                                                @case('price')
                                                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M13.6 2.5H7.8v4.1L3.5 11l9.5 9.5 7.5-7.5L13.6 6.1V2.5Zm-3.2 6.3a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm3.8 6.1h-2.5v1.3h-1.4v-1.3H8.8v-1.3h1.5v-.8H8.8v-1.3h1.5v-1.3h1.4v1.3h2.5v1.3h-2.5v.8h2.5v1.3Z"/></svg>
                                                    @break
                                                @case('delivery')
                                                    <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24"><path d="M3 5h11v10H3V5Zm11 3h4l3 4v3h-7V8ZM7 19a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Zm10 0a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/></svg>
                                                    @break
                                                @case('quality')
                                                    <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24"><path d="M5 8h14l-1 13H6L5 8Zm3-5 2 4H8L6 3h2Zm5 0v4h-2V3h2Zm5 0-2 4h-2l2-4h2Z"/></svg>
                                                    @break
                                                @case('payment')
                                                    <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24"><path d="M3 5h18v4H3V5Zm0 6h18v8H3v-8Zm13 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/></svg>
                                                    @break
                                                @default
                                                    <svg class="h-7 w-7" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3a8 8 0 0 0-8 8v2a3 3 0 0 0 3 3h1v-6H6.1a6 6 0 0 1 11.8 0H16v6h1.4A5.5 5.5 0 0 1 13 19.9V18h-2v4h2a7.5 7.5 0 0 0 7-4.8A3 3 0 0 0 21 15v-4a8 8 0 0 0-9-8Z"/></svg>
                                            @endswitch
                                        </span>
                                        <span class="whitespace-nowrap">{{ $highlight['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
    @if (! $catalogItem && $categories->isNotEmpty())
        <section id="shop-categories" class="bg-[#f8fafc] py-[25px] dark:bg-[#111827]" x-data>
            <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8">
                <div class="relative">
                    <div x-ref="categoryRail" class="category-rail flex min-w-0 snap-x snap-mandatory gap-3 overflow-x-auto px-1 pb-2 md:gap-4">
                        @foreach ($categories as $category)
                            @php
                                $categoryImage = $category->navigation_image_path ?: $category->thumbnail_path ?: $category->header_menu_image_path;
                            @endphp
                            <a href="{{ route('catalog.show', $category->slug) }}" class="group flex aspect-square w-[130px] shrink-0 snap-start flex-col items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-white p-4 text-center shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md sm:w-[150px]" aria-label="Browse {{ $category->name }}">
                                <span class="grid h-20 w-full place-items-center overflow-hidden">
                                    @if ($categoryImage)
                                        <img src="{{ asset('storage/'.$categoryImage) }}" alt="{{ $category->name }}" class="h-full w-full object-contain transition duration-300 group-hover:scale-105">
                                    @else
                                        <svg class="h-12 w-12 text-slate-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="11" rx="1.5" stroke-width="1.5"/><path stroke-linecap="round" stroke-width="1.5" d="M7 8h3m-3 3h10M8 19v-3m8 3v-3m-5-8h6"/></svg>
                                    @endif
                                </span>
                                <span class="mt-2 block w-full truncate text-xs font-normal text-red-500 transition group-hover:text-red-600 sm:text-sm">{{ $category->name }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif
    @if (! $catalogItem && $bestSellingProducts->isNotEmpty())
        <section id="best-selling-products" class="bg-[#f8fafc] py-10 dark:bg-[#111827] sm:py-14">
            <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8">
                <div class="mb-7 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold text-orange-500">Customer favourites</p>
                        <h2 class="mt-1 text-2xl font-normal tracking-tight text-slate-950 sm:text-3xl">Best Selling Products</h2>
                    </div>
                    <a href="{{ route('storefront.best-selling') }}" class="shrink-0 text-sm font-semibold text-indigo-600 transition hover:text-indigo-800 dark:text-indigo-400">View all <span aria-hidden="true">&rarr;</span></a>
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5 lg:gap-5">
                    @foreach ($bestSellingProducts as $bestSellingProduct)
                        <div class="relative">
                            @if ((int) $bestSellingProduct->sold_quantity > 0)
                                <span class="absolute left-3 top-3 z-10 rounded-full bg-orange-500 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-white shadow-sm">{{ (int) $bestSellingProduct->sold_quantity }} sold</span>
                            @endif
                            <x-store-product-card :product="$bestSellingProduct" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
    @php
        $flashSale = array_merge(\App\Models\WebsiteSetting::defaultFlashSaleSettings(), $websiteSettings?->flash_sale_settings ?? []);
        $flashSaleEndsAt = filled($flashSale['ends_at']) ? \Carbon\Carbon::parse($flashSale['ends_at']) : null;
        $flashSaleRemaining = $flashSaleEndsAt?->isFuture() ? (int) now()->diffInSeconds($flashSaleEndsAt) : 0;
    @endphp
    @if (! $catalogItem && $flashSale['enabled'])
        <section aria-label="Flash sale" class="bg-[#f8fafc] py-[25px] dark:bg-[#111827]">
            <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8">
                <div x-data="{ remaining: {{ $flashSaleRemaining }}, timer: null, init() { if (this.remaining <= 0) return; this.timer = window.setInterval(() => { if (this.remaining > 0) this.remaining--; if (this.remaining <= 0) window.clearInterval(this.timer); }, 1000); }, destroy() { window.clearInterval(this.timer); }, pad(value) { return String(value).padStart(2, '0'); }, get hours() { return this.pad(Math.floor(this.remaining / 3600)); }, get minutes() { return this.pad(Math.floor((this.remaining % 3600) / 60)); }, get seconds() { return this.pad(this.remaining % 60); } }" style="background-image: linear-gradient(to right, {{ $flashSale['background_from'] }}, {{ $flashSale['background_via'] }}, {{ $flashSale['background_to'] }});" class="mx-auto max-w-[1400px] overflow-hidden rounded-2xl text-white shadow-[0_6px_18px_rgba(224,48,144,.14)] ring-1 ring-white/5 dark:shadow-[0_6px_18px_rgba(0,0,0,.22)]">
                    <div class="grid gap-6 px-5 py-6 sm:px-7 lg:grid-cols-[auto_minmax(260px,1fr)_auto] lg:items-center lg:gap-10 lg:px-8">
                        <div class="flex items-center gap-4"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-[#f8003f] shadow-lg shadow-rose-950/30"><svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m13 2-9 12h7l-1 8 9-12h-7l1-8Z"/></svg></span><div><p class="text-[11px] font-semibold uppercase tracking-[.11em] text-rose-100/70">{{ $flashSale['eyebrow'] }}</p><h2 class="mt-1 text-2xl font-medium tracking-tight">{{ $flashSale['title'] }}</h2></div></div>
                        <p class="max-w-md text-sm leading-5 text-rose-50/75">{{ $flashSale['description'] }}</p>
                        <div class="flex flex-wrap items-center gap-3 sm:flex-nowrap lg:justify-end">
                            <span class="mr-1 text-[11px] font-semibold uppercase tracking-wide text-rose-100/65">Ends in</span>
                            <div class="flex items-center gap-2" aria-label="Flash sale countdown"><template x-for="unit in [{ value: hours, label: 'Hrs' }, { value: minutes, label: 'Min' }, { value: seconds, label: 'Sec' }]" :key="unit.label"><div class="flex items-center gap-2"><div class="min-w-14 rounded-xl border border-white/15 bg-white/[.07] px-2 py-2.5 text-center shadow-inner"><strong class="block font-mono text-xl leading-none" x-text="unit.value"></strong><span class="mt-1.5 block text-[9px] font-semibold uppercase text-rose-100/55" x-text="unit.label"></span></div><span x-show="unit.label !== 'Sec'" class="text-lg font-bold text-rose-100/40">:</span></div></template></div>
                            <a href="{{ route('storefront.flash-sale') }}" class="ml-auto inline-flex min-h-11 items-center justify-center gap-2 rounded-full bg-gradient-to-r from-rose-100 to-rose-200 px-6 text-sm font-bold text-[#450611] shadow-lg shadow-black/10 transition hover:-translate-y-0.5 hover:from-white hover:to-rose-100 sm:ml-3">{{ $flashSale['button_text'] }}<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 18 6-6-6-6"/></svg></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
    @if (! $catalogItem && ! request('search') && $newArrivalProducts->isNotEmpty())
        <section id="new-arrivals" class="scroll-mt-28 bg-[#f8fafc] py-10 dark:bg-[#111827] sm:py-14">
            <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8">
                <div class="mb-7 flex items-end justify-between gap-4">
                    <div>
                        <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400">Just landed</p>
                        <h2 class="mt-1 text-2xl font-normal tracking-tight text-slate-950 dark:text-white sm:text-3xl">New Arrivals</h2>
                    </div>
                    <a href="{{ route('storefront.new-arrivals') }}" class="shrink-0 text-sm font-semibold text-indigo-600 transition hover:text-indigo-800 dark:text-indigo-400">View all <span aria-hidden="true">&rarr;</span></a>
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5 lg:gap-5">
                    @foreach ($newArrivalProducts as $newArrivalProduct)
                        <div class="relative">
                            <span class="absolute left-3 z-10 rounded-full bg-indigo-600 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[.12em] text-white shadow-sm {{ $newArrivalProduct->sale_price && $newArrivalProduct->price > $newArrivalProduct->sale_price ? 'top-11' : 'top-3' }}">New</span>
                            <x-store-product-card :product="$newArrivalProduct" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
    <section id="latest-products" class="mx-auto max-w-[1400px] scroll-mt-28 bg-[#f8fafc] px-4 py-10 dark:bg-[#111827] sm:px-6 sm:py-14 lg:px-8 lg:py-16">
        @if (! $catalogItem)
            <div class="mb-7 flex items-end justify-between sm:mb-8">
                <div>
                    <p class="text-sm font-bold text-indigo-600">Our collection</p>
                    <h2 class="mt-1 text-2xl font-normal tracking-tight sm:text-3xl">{{ request('search') ? 'Search results' : 'Latest products' }}</h2>
                </div>
                <div class="flex shrink-0 items-center gap-4">
                    <p class="text-sm text-slate-500">{{ $products->total() }} products</p>
                    @if (! request('search'))
                        <a href="{{ route('storefront.latest-products') }}" class="text-sm font-semibold text-indigo-600 transition hover:text-indigo-800 dark:text-indigo-400">View all <span aria-hidden="true">&rarr;</span></a>
                    @endif
                </div>
            </div>
        @endif
        <div class="grid gap-7 {{ $catalogItem ? 'lg:grid-cols-[260px_minmax(0,1fr)]' : '' }}">
            @if ($catalogItem)
                <aside>
                    <form method="GET" action="{{ route('catalog.show', $catalogItem->slug) }}" class="space-y-3 lg:sticky lg:top-28">
                        <section x-data="{ open: true, minPrice: {{ (float) request('min_price', $minimumProductPrice) }}, maxPrice: {{ (float) request('max_price', $maximumProductPrice) }}, floor: {{ (float) $minimumProductPrice }}, limit: {{ max((float) $minimumProductPrice + 1, (float) $maximumProductPrice) }} }" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <button type="button" @click="open = !open" class="flex w-full items-center justify-between border-b border-slate-200 px-5 py-4 text-left text-base font-bold"><span>Price Range</span><svg class="h-4 w-4 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg></button>
                            <div x-show="open" class="p-5">
                                <div class="relative mb-6 h-5">
                                    <div class="absolute inset-x-0 top-2 h-1.5 rounded-full bg-slate-200"></div>
                                    <div class="absolute top-2 h-1.5 rounded-full bg-orange-500" :style="`left: ${((minPrice - floor) / (limit - floor)) * 100}%; right: ${100 - ((maxPrice - floor) / (limit - floor)) * 100}%`"></div>
                                    <input type="range" :min="floor" :max="limit" step="1" x-model.number="minPrice" @input="if (minPrice > maxPrice) minPrice = maxPrice" @change="$el.form.requestSubmit()" aria-label="Minimum price" class="price-range-input absolute inset-x-0 top-0 w-full">
                                    <input type="range" :min="floor" :max="limit" step="1" x-model.number="maxPrice" @input="if (maxPrice < minPrice) maxPrice = minPrice" @change="$el.form.requestSubmit()" aria-label="Maximum price" class="price-range-input absolute inset-x-0 top-0 w-full">
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div><label for="min_price" class="mb-1 block text-xs text-slate-500">Minimum</label><input id="min_price" name="min_price" type="number" :min="floor" :max="maxPrice" x-model.number="minPrice" @input.debounce.600ms="$el.form.requestSubmit()" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm"></div>
                                    <div><label for="max_price" class="mb-1 block text-xs text-slate-500">Maximum</label><input id="max_price" name="max_price" type="number" :min="minPrice" :max="limit" x-model.number="maxPrice" @input.debounce.600ms="$el.form.requestSubmit()" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm"></div>
                                </div>
                            </div>
                        </section>
                        <section x-data="{ open: true }" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <button type="button" @click="open = !open" class="flex w-full items-center justify-between border-b border-slate-200 px-5 py-4 text-left text-base font-bold"><span>Availability</span><svg class="h-4 w-4 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg></button>
                            <div x-show="open" class="space-y-3 p-5 text-sm">
                                @foreach (['in_stock' => 'In Stock', 'pre_order' => 'Pre Order', 'upcoming' => 'Upcoming'] as $value => $label)
                                    <label class="flex cursor-pointer items-center gap-3"><input type="checkbox" name="availability[]" value="{{ $value }}" @checked(in_array($value, request()->array('availability'))) @change="$el.form.requestSubmit()" class="rounded border-slate-300 text-orange-500 focus:ring-orange-500"><span>{{ $label }}</span></label>
                                @endforeach
                            </div>
                        </section>
                        @if (request()->hasAny(['min_price', 'max_price', 'availability']))
                            <a href="{{ route('catalog.show', $catalogItem->slug) }}" class="block text-center text-sm font-semibold text-slate-500 hover:text-rose-600">Clear filters</a>
                        @endif
                    </form>
                </aside>
            @endif
            <div>
                @if ($catalogItem)
                    <form method="GET" action="{{ route('catalog.show', $catalogItem->slug) }}" class="mb-5 flex flex-col gap-4 rounded-xl border border-slate-200 bg-white px-4 py-3 sm:flex-row sm:items-center">
                        @foreach (request()->except(['per_page', 'sort', 'page']) as $key => $value)
                            @if (is_array($value))
                                @foreach ($value as $item)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                                @endforeach
                            @else
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endif
                        @endforeach
                        <h2 class="min-w-0 flex-1 truncate text-sm font-bold text-slate-900 sm:text-base">{{ $catalogItem->name }}</h2>
                        <div class="flex flex-wrap items-center gap-3 text-sm">
                            <label for="per_page" class="text-slate-600">Show:</label>
                            <select id="per_page" name="per_page" onchange="this.form.submit()" class="rounded-md border-slate-200 bg-slate-50 py-2 pl-3 pr-8 text-sm font-medium focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ([12, 20, 40, 60] as $count)
                                    <option value="{{ $count }}" @selected($products->perPage() === $count)>{{ $count }}</option>
                                @endforeach
                            </select>
                            <label for="sort" class="ml-1 text-slate-600">Sort By:</label>
                            <select id="sort" name="sort" onchange="this.form.submit()" class="min-w-32 rounded-md border-slate-200 bg-slate-50 py-2 pl-3 pr-8 text-sm font-medium focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="" @selected(! request('sort'))>Default</option>
                                <option value="price_low" @selected(request('sort') === 'price_low')>Price: Low to High</option>
                                <option value="price_high" @selected(request('sort') === 'price_high')>Price: High to Low</option>
                                <option value="name_asc" @selected(request('sort') === 'name_asc')>Name: A to Z</option>
                                <option value="name_desc" @selected(request('sort') === 'name_desc')>Name: Z to A</option>
                            </select>
                        </div>
                    </form>
                @endif
                @if ($products->isNotEmpty())
                    @php
                        $usesInfiniteProducts = request()->routeIs('storefront.index');
                        $nextProductPageUrl = $products->nextPageUrl();
                        if ($usesInfiniteProducts && $nextProductPageUrl) {
                            $nextProductPageUrl .= str_contains($nextProductPageUrl, '?') ? '&infinite=1' : '?infinite=1';
                        }
                    @endphp
                    <div @if($usesInfiniteProducts) id="storefront-product-grid" data-next-url="{{ $nextProductPageUrl }}" data-product-limit="100" data-product-total="{{ $products->total() }}" @endif class="grid grid-cols-1 gap-5 sm:grid-cols-2 {{ $catalogItem ? 'xl:grid-cols-4' : 'lg:grid-cols-5' }} lg:gap-5">
                        @foreach ($products as $product)
                            <x-store-product-card :product="$product" />
                        @endforeach
                    </div>
                    @if ($usesInfiniteProducts)
                        <div id="storefront-product-loader" class="mt-8 flex min-h-12 items-center justify-center" aria-live="polite">
                            @if ($products->hasMorePages())
                                <div data-loading-indicator class="hidden items-center gap-3 text-sm font-semibold text-slate-500"><span class="h-5 w-5 animate-spin rounded-full border-2 border-slate-200 border-t-sky-500"></span>Loading more products...</div>
                                <div data-infinite-sentinel class="h-2 w-full" aria-hidden="true"></div>
                            @endif
                        </div>
                        <div id="storefront-shop-link" class="mt-8 hidden text-center"><a href="{{ route('storefront.shop') }}" class="inline-flex items-center gap-2 rounded-xl bg-slate-950 px-6 py-3 text-sm font-bold text-white transition hover:bg-sky-600">View All Products <span aria-hidden="true">&rarr;</span></a></div>
                    @elseif ($catalogItem || $products->hasPages())
                        <nav aria-label="Product pagination" class="mt-8 flex flex-col gap-4 border-y border-slate-200 py-4 sm:mt-10 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex flex-wrap items-center gap-1 text-xs font-semibold">
                                @if ($products->onFirstPage())
                                    <span class="px-2 py-2 text-slate-400">PREV</span>
                                @else
                                    <a href="{{ $products->previousPageUrl() }}" class="px-2 py-2 text-slate-700 hover:text-indigo-600">PREV</a>
                                @endif
                                @php
                                    $firstPaginationPage = max(1, $products->currentPage() - 2);
                                    $lastPaginationPage = min($products->lastPage(), $products->currentPage() + 2);
                                @endphp
                                @if ($firstPaginationPage > 1)
                                    <a href="{{ $products->url(1) }}" class="grid h-9 min-w-9 place-items-center rounded px-2 text-slate-700 transition hover:bg-slate-200">1</a>
                                @endif
                                @if ($firstPaginationPage > 2)
                                    <span class="px-2 py-2 text-slate-400">…</span>
                                @endif
                                @foreach (range($firstPaginationPage, $lastPaginationPage) as $page)
                                    @if ($page === $products->currentPage())
                                        <span aria-current="page" class="grid h-9 min-w-9 place-items-center rounded bg-orange-600 px-2 text-white">{{ $page }}</span>
                                    @else
                                        <a href="{{ $products->url($page) }}" class="grid h-9 min-w-9 place-items-center rounded px-2 text-slate-700 transition hover:bg-slate-200">{{ $page }}</a>
                                    @endif
                                @endforeach
                                @if ($lastPaginationPage < $products->lastPage() - 1)
                                    <span class="px-2 py-2 text-slate-400">…</span>
                                @endif
                                @if ($lastPaginationPage < $products->lastPage())
                                    <a href="{{ $products->url($products->lastPage()) }}" class="grid h-9 min-w-9 place-items-center rounded px-2 text-slate-700 transition hover:bg-slate-200">{{ $products->lastPage() }}</a>
                                @endif
                                @if ($products->hasMorePages())
                                    <a href="{{ $products->nextPageUrl() }}" class="px-2 py-2 text-slate-700 hover:text-indigo-600">NEXT</a>
                                @else
                                    <span class="px-2 py-2 text-slate-400">NEXT</span>
                                @endif
                            </div>
                            <p class="text-xs font-medium text-slate-600">Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} ({{ $products->lastPage() }} {{ str('Page')->plural($products->lastPage()) }})</p>
                        </nav>
                    @endif
                @else
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center sm:py-20"><h2 class="text-xl font-bold">No products found</h2><p class="mt-2 text-sm text-slate-500">Try changing or clearing the current filters.</p><a href="{{ $catalogItem ? route('catalog.show', $catalogItem->slug) : route('storefront.index') }}" class="mt-5 inline-flex rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white">Clear filters</a></div>
                @endif
                @if ($catalogItem)
                    <section class="mt-8 rounded-xl border border-slate-200 bg-white p-5 sm:p-6">
                        <h2 class="text-xl font-semibold text-indigo-700">{{ $catalogItem->name }}</h2>
                        @if ($missingCatalogDescription)
                            <p class="mt-4 rounded-lg border border-dashed border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-800">Description is coming soon. Our team will update it as soon as possible.</p>
                        @else
                            <div class="prose prose-slate mt-4 max-w-none text-sm leading-7 text-slate-700">{!! $bottomCatalogDescription !!}</div>
                        @endif
                    </section>
                @endif
            </div>
        </div>
    </section>

    @if (request()->routeIs('storefront.index'))
        <section id="why-choose-us" class="bg-[#f8fafc] py-10 dark:bg-[#111827] sm:py-14">
            <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8">
                <div class="mx-auto max-w-2xl text-center">
                    <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400">Shop with confidence</p>
                    <h2 class="mt-1 text-2xl font-normal tracking-tight text-slate-950 sm:text-3xl">Why Choose {{ $websiteSettings?->site_name ?? config('app.name') }}?</h2>
                </div>
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([
                        ['Genuine Products', 'Carefully selected products with dependable quality.', 'check'],
                        ['Competitive Pricing', 'Fair prices and worthwhile offers across our collection.', 'price'],
                        ['Nationwide Delivery', 'Reliable doorstep delivery throughout Bangladesh.', 'delivery'],
                        ['Customer Support', 'Helpful assistance before and after every purchase.', 'support'],
                    ] as [$title, $copy, $icon])
                        <article class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-center dark:border-slate-700 dark:bg-slate-800/60 sm:p-6">
                            <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300">
                                @if ($icon === 'delivery')
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h11v10H3V7Zm11 3h3l4 4v3h-7v-7ZM7 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg>
                                @elseif ($icon === 'price')
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 3 4 11l9 9 7-7-8-10Zm-2 6h.01"/></svg>
                                @elseif ($icon === 'support')
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 13v-2a8 8 0 0 1 16 0v2M4 13a3 3 0 0 0 3 3h1v-6H7a3 3 0 0 0-3 3Zm16 0a3 3 0 0 1-3 3h-1v-6h1a3 3 0 0 1 3 3Zm0 2c0 3-2 5-6 5"/></svg>
                                @else
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m5 12 4 4L19 6"/></svg>
                                @endif
                            </span>
                            <h3 class="mt-4 font-bold text-slate-950">{{ $title }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500">{{ $copy }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        @if ($latestReviews->isNotEmpty())
            <section id="customer-reviews" class="bg-[#f8fafc] py-10 dark:bg-[#111827] sm:py-14">
                <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8">
                    <div class="mx-auto max-w-2xl text-center">
                        <p class="text-sm font-bold text-amber-600 dark:text-amber-400">Verified experiences</p>
                        <h2 class="mt-1 text-2xl font-normal tracking-tight text-slate-950 sm:text-3xl">What Our Customers Say</h2>
                    </div>
                    <div class="mt-8 grid gap-5 lg:grid-cols-3">
                        @foreach ($latestReviews as $review)
                            <article class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-[#161f2e]">
                                <div class="flex gap-1 text-lg leading-none" aria-label="{{ $review->rating }} out of 5 stars">
                                    @foreach (range(1, 5) as $star)<span class="{{ $star <= $review->rating ? 'text-amber-400' : 'text-slate-300 dark:text-slate-600' }}">&#9733;</span>@endforeach
                                </div>
                                <blockquote class="mt-4 flex-1 text-sm leading-7 text-slate-600">&ldquo;{{ Str::limit($review->body, 220) }}&rdquo;</blockquote>
                                <div class="mt-5 border-t border-slate-100 pt-4 dark:border-slate-800">
                                    <p class="font-bold text-slate-950">{{ $review->user->name }}</p>
                                    <a href="{{ route('catalog.show', $review->product->slug) }}" class="mt-1 block truncate text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ $review->product->title }}</a>
                                    <span class="mt-2 inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300">Verified purchase</span>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section id="about-us" class="bg-[#f8fafc] py-10 dark:bg-[#111827] sm:py-14">
            <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800/50 sm:p-8 lg:p-10">
                    @if (filled($websiteSettings?->footer_content))
                        <div class="mx-auto max-w-5xl text-sm leading-7 text-slate-600 [&_a]:font-semibold [&_a]:text-indigo-600 [&_h1]:mb-5 [&_h1]:text-3xl [&_h1]:font-bold [&_h1]:text-slate-950 [&_h2]:mb-4 [&_h2]:mt-7 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:text-slate-950 [&_h3]:mb-3 [&_h3]:mt-6 [&_h3]:text-xl [&_h3]:font-bold [&_h3]:text-slate-950 [&_li]:my-1 [&_ol]:my-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:my-4 [&_strong]:text-slate-800 [&_ul]:my-4 [&_ul]:list-disc [&_ul]:pl-6">{!! $websiteSettings->footer_content !!}</div>
                    @else
                        <div class="mx-auto max-w-4xl text-center">
                            <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400">About our store</p>
                            <h2 class="mt-1 text-2xl font-normal tracking-tight text-slate-950 sm:text-3xl">A simpler way to shop online in Bangladesh</h2>
                            <p class="mt-5 text-sm leading-7 text-slate-600 sm:text-base">{{ $websiteSettings?->site_name ?? config('app.name') }} brings carefully selected products, transparent prices and dependable nationwide delivery together in one convenient online store. We focus on straightforward ordering, responsive customer support and a secure shopping experience so you can choose the right product with confidence.</p>
                            <a href="{{ route('storefront.shop') }}" class="mt-6 inline-flex min-h-11 items-center rounded-xl bg-indigo-600 px-5 text-sm font-bold text-white transition hover:bg-indigo-700">Explore all products</a>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        @php
            $homepageFaqs = collect($websiteSettings?->homepage_faqs ?? \App\Models\WebsiteSetting::defaultHomepageFaqs())
                ->filter(fn ($faq) => filled(data_get($faq, 'question')) && filled(data_get($faq, 'answer')))
                ->values();
            $homepageFaqSchema = $homepageFaqs->isNotEmpty() ? [
                '@'.'context' => 'https://schema.org',
                '@'.'type' => 'FAQPage',
                'mainEntity' => $homepageFaqs->map(fn ($faq) => [
                    '@'.'type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => ['@'.'type' => 'Answer', 'text' => $faq['answer']],
                ])->all(),
            ] : null;
        @endphp
        <section id="frequently-asked-questions" class="bg-[#f8fafc] py-10 dark:bg-[#111827] sm:py-14">
            <div class="mx-auto grid max-w-[1400px] gap-8 px-4 sm:px-6 lg:grid-cols-[.75fr_1.25fr] lg:px-8">
                <div>
                    <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400">Need help?</p>
                    <h2 class="mt-1 text-2xl font-normal tracking-tight text-slate-950 sm:text-3xl">Frequently Asked Questions</h2>
                    <p class="mt-4 max-w-md text-sm leading-7 text-slate-500">Quick answers about ordering, delivery, payment and product quality.</p>
                    <a href="tel:{{ $websiteSettings?->business_phone ?: '+8801736741793' }}" class="mt-5 inline-flex text-sm font-bold text-indigo-600 dark:text-indigo-400">Call customer support &rarr;</a>
                </div>
                <div class="space-y-3" x-data="{ open: 0 }">
                    @foreach ($homepageFaqs as $faqIndex => $faq)
                        <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-[#161f2e]">
                            <button type="button" @click="open = open === {{ $faqIndex }} ? null : {{ $faqIndex }}" :aria-expanded="open === {{ $faqIndex }}" class="flex w-full items-center justify-between gap-4 px-5 py-4 text-left font-bold text-slate-950 sm:px-6">
                                <span>{{ $faq['question'] }}</span>
                                <svg class="h-4 w-4 shrink-0 transition" :class="open === {{ $faqIndex }} && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg>
                            </button>
                            <div x-show="open === {{ $faqIndex }}" x-transition class="border-t border-slate-100 px-5 py-4 text-sm leading-7 text-slate-600 dark:border-slate-800 sm:px-6">{{ $faq['answer'] }}</div>
                        </article>
                    @endforeach
                    @if ($homepageFaqs->isEmpty())
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center dark:border-slate-700 dark:bg-[#161f2e]">
                            <p class="font-semibold text-slate-700 dark:text-slate-200">No FAQs available.</p>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Helpful answers will be added here soon.</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>
        @if ($homepageFaqSchema)
            <script type="application/ld+json">{!! json_encode($homepageFaqSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
        @endif

        @if ($latestBlogPosts->isNotEmpty())
            <section id="latest-from-blog" class="bg-[#f8fafc] py-10 dark:bg-[#111827] sm:py-14">
                <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8">
                    <div class="mb-7 flex items-end justify-between gap-4">
                        <div>
                            <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400">Guides &amp; ideas</p>
                            <h2 class="mt-1 text-2xl font-normal tracking-tight text-slate-950 sm:text-3xl">Latest From Our Blog</h2>
                        </div>
                        <a href="{{ route('storefront.blog.index') }}" class="shrink-0 text-sm font-semibold text-indigo-600 dark:text-indigo-400">View all posts &rarr;</a>
                    </div>
                    <div class="grid gap-5 md:grid-cols-3">
                        @foreach ($latestBlogPosts as $post)
                            <article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-slate-800/50">
                                <a href="{{ route('storefront.blog.show', $post) }}" class="block">
                                    @if ($post->featured_image_path)
                                        <img src="{{ asset('storage/'.$post->featured_image_path) }}" alt="{{ $post->title }}" loading="lazy" class="aspect-[16/9] w-full object-cover">
                                    @else
                                        <div class="grid aspect-[16/9] place-items-center bg-gradient-to-br from-indigo-500 to-violet-700 text-white"><svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg></div>
                                    @endif
                                </a>
                                <div class="p-5">
                                    <p class="text-xs font-semibold text-slate-400">{{ $post->published_at->format('d M Y') }}</p>
                                    <h3 class="mt-2 line-clamp-2 text-lg font-bold leading-6 text-slate-950"><a href="{{ route('storefront.blog.show', $post) }}" class="transition group-hover:text-indigo-600 dark:group-hover:text-indigo-400">{{ $post->title }}</a></h3>
                                    @if ($post->excerpt)<p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-500">{{ $post->excerpt }}</p>@endif
                                    <a href="{{ route('storefront.blog.show', $post) }}" class="mt-4 inline-flex text-sm font-bold text-indigo-600 dark:text-indigo-400">Read article &rarr;</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    @endif
</x-storefront-layout>
