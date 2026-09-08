@props(['product'])
@php
    $cartQuantity = (int) data_get(session('cart', []), $product->id, 0);
    $hasDiscount = $product->sale_price !== null && $product->price !== null && (float) $product->price > 0 && (float) $product->sale_price < (float) $product->price;
    $discountPercentage = $hasDiscount ? (int) round((((float) $product->price - (float) $product->sale_price) / (float) $product->price) * 100) : 0;
@endphp
<article data-store-product-card class="ds-card group flex h-full flex-col overflow-hidden shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-slate-200/70">
    <a href="{{ route('catalog.show', $product->slug) }}" class="relative block aspect-square overflow-hidden bg-white">
        @if ($product->featured_image_path)<img src="{{ asset('storage/'.$product->featured_image_path) }}" alt="{{ $product->title }}" loading="lazy" class="h-full w-full object-contain transition duration-300 group-hover:scale-[1.02]">@else<div class="grid h-full place-items-center bg-slate-100 text-slate-300"><svg class="h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.3" d="m2.25 15.75 5.16-5.16a2.25 2.25 0 0 1 3.18 0l5.16 5.16m-1.5-1.5 1.66-1.66a2.25 2.25 0 0 1 3.18 0l2.66 2.66M4.5 19.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/></svg></div>@endif
        @if ($hasDiscount)<span class="absolute left-3 top-3 rounded-full bg-rose-500 px-2.5 py-1 text-xs font-bold text-white">Sale</span>@endif
        @if ($cartQuantity > 0)<span class="absolute right-3 top-3 rounded-full bg-emerald-600 px-2.5 py-1 text-xs font-bold text-white shadow-sm">In Cart &middot; Qty {{ $cartQuantity }}</span>@endif
    </a>
    <div class="flex flex-1 flex-col p-4 sm:p-5">
        <p class="text-xs font-bold uppercase tracking-wider text-indigo-600">{{ $product->brand?->name ?? $product->categories->first()?->name ?? 'Featured' }}</p>
        <h2 class="mt-2 line-clamp-2 text-base font-medium leading-6"><a href="{{ route('catalog.show', $product->slug) }}" class="hover:text-indigo-600">{{ $product->title }}</a></h2>
        <div class="mt-auto flex items-end justify-between gap-3 pt-4"><div class="flex flex-wrap items-center gap-x-1.5 gap-y-1"><span class="text-xl font-semibold text-slate-900 dark:text-white"><span class="currency-symbol">&#2547;</span>{{ number_format($product->current_price, 2) }}</span>@if ($hasDiscount)<span class="text-sm text-slate-400 line-through"><span class="currency-symbol">&#2547;</span>{{ number_format((float) $product->price, 2) }}</span><span data-discount-percentage class="rounded-full bg-amber-100 px-2 py-1 text-[10px] font-extrabold leading-none text-amber-700 dark:bg-amber-400/15 dark:text-amber-300">{{ $discountPercentage }}% OFF</span>@endif</div><span class="shrink-0 text-xs font-semibold {{ $product->isDigital() || $product->stock_quantity > 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ $product->isDigital() ? 'Instant download' : ($product->stock_quantity > 0 ? 'In stock' : 'Sold out') }}</span></div>
    </div>
</article>
