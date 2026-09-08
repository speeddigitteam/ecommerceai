@props([
    'catalogItem',
    'activeCategory' => null,
    'categoryBreadcrumbs',
    'descriptionSource' => null,
    'description' => null,
    'catalogLinks' => [],
    'productCount' => 0,
    'flashSaleSettings' => [],
])
@php
    $flashSaleColors = array_merge(\App\Models\WebsiteSetting::defaultFlashSaleSettings(), $flashSaleSettings);
    $itemType = $activeCategory ? 'Category' : 'Brand';
    $itemLabel = $activeCategory?->parentRecursive?->name ?? ($activeCategory ? 'Categories' : 'Brand');
    $plainDescription = filled($descriptionSource)
        ? str(strip_tags($descriptionSource))->squish()->toString()
        : $description;
@endphp
<section data-catalog-hero class="border-b border-slate-200/80 bg-white py-5 dark:border-slate-800 dark:bg-[#111827] sm:py-7">
    <div class="mx-auto max-w-[1400px] px-4 sm:px-6 lg:px-8">
        <nav aria-label="Breadcrumb" class="mb-5 flex min-w-0 flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400 sm:text-sm">
            <a href="{{ route('storefront.index') }}" class="transition hover:text-indigo-600 dark:hover:text-indigo-400">Home</a>
            <svg class="h-3.5 w-3.5 shrink-0 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/></svg>
            @if ($activeCategory)
                <a href="{{ route('storefront.index').'#shop-categories' }}" class="transition hover:text-indigo-600 dark:hover:text-indigo-400">Categories</a>
                @foreach ($categoryBreadcrumbs as $breadcrumbCategory)
                    <svg class="h-3.5 w-3.5 shrink-0 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/></svg>
                    @if ($loop->last)<span class="font-semibold text-slate-900 dark:text-white" aria-current="page">{{ $breadcrumbCategory->name }}</span>@else<a href="{{ route('catalog.show', $breadcrumbCategory->slug) }}" class="transition hover:text-indigo-600 dark:hover:text-indigo-400">{{ $breadcrumbCategory->name }}</a>@endif
                @endforeach
            @else
                <span>Brands</span><svg class="h-3.5 w-3.5 shrink-0 text-slate-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 18 6-6-6-6"/></svg><span class="font-semibold text-slate-900 dark:text-white" aria-current="page">{{ $catalogItem->name }}</span>
            @endif
        </nav>

        <div class="relative isolate overflow-hidden rounded-3xl bg-[#0d1728] px-6 py-9 text-white shadow-[0_12px_30px_rgba(15,23,42,.16)] sm:px-10 sm:py-11 lg:px-12">
            <div class="absolute inset-0 -z-20" style="background-image: linear-gradient(to right, {{ $flashSaleColors['background_from'] }}, {{ $flashSaleColors['background_via'] }}, {{ $flashSaleColors['background_to'] }});"></div>
            <div class="absolute inset-0 -z-10 opacity-30" style="background-image: radial-gradient(rgba(255,255,255,.22) 1px, transparent 1px); background-size: 22px 22px;"></div>
            <div class="max-w-4xl">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/[.07] px-4 py-2 text-[11px] font-bold uppercase tracking-[.12em] text-slate-200"><span class="text-rose-400">&lsaquo;</span>{{ $itemLabel }}</span>
                <h1 class="mt-5 text-3xl font-bold tracking-[-.035em] sm:text-4xl lg:text-[42px]">{{ $catalogItem->name }}</h1>
                <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-300 sm:text-base">{{ $plainDescription }}</p>
                <div class="mt-6 flex flex-wrap gap-2.5 text-xs font-semibold sm:text-sm">
                    <span class="inline-flex min-h-9 items-center gap-2 rounded-full bg-rose-600 px-4 text-white shadow-lg shadow-rose-950/20"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16v12H4zM8 7V5h8v2M8 11h8"/></svg>{{ $productCount }} {{ str('product')->plural($productCount) }}</span>
                    <span class="inline-flex min-h-9 items-center gap-2 rounded-full border border-white/15 bg-white/[.06] px-4 text-slate-200"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="6" width="18" height="12" rx="2" stroke-width="1.7"/><path stroke-width="1.7" d="M7 14h4"/></svg>Cash on Delivery</span>
                    <span class="inline-flex min-h-9 items-center gap-2 rounded-full border border-white/15 bg-white/[.06] px-4 text-slate-200"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 7h11v10H3zM14 10h4l3 3v4h-7zM7 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg>Nationwide delivery</span>
                    <span class="inline-flex min-h-9 items-center gap-2 rounded-full border border-white/15 bg-white/[.06] px-4 text-slate-200"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M12 3 4.5 6v5c0 4.8 3.2 8.1 7.5 10 4.3-1.9 7.5-5.2 7.5-10V6L12 3Z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="m9 12 2 2 4-4"/></svg>Quality products</span>
                </div>
            </div>
        </div>

        @if ($catalogLinks !== [])
            <div class="mt-4 flex flex-wrap items-center gap-2"><span class="mr-1 text-xs font-bold uppercase tracking-wider text-slate-400">Browse</span>@foreach ($catalogLinks as $catalogLink)<a href="{{ $catalogLink['url'] }}" class="rounded-full border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-indigo-400 hover:text-indigo-700 dark:border-slate-700 dark:bg-[#161f2e] dark:text-slate-300 dark:hover:border-indigo-500 dark:hover:text-indigo-300 sm:text-sm">{{ $catalogLink['name'] }}</a>@endforeach</div>
        @endif
    </div>
</section>