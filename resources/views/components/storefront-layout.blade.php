<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="storefrontChrome()" x-init="init()" :class="{ dark: darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $pageTitle = $title ?: ($websiteSettings?->seo_title ?? 'Shop');
        $documentTitle = ($includeSiteName ?? true) ? $pageTitle.' | '.($websiteSettings?->site_name ?? config('app.name')) : $pageTitle;
    @endphp
    <title>{{ $documentTitle }}</title>
    <meta name="description" content="{{ $description ?? $websiteSettings?->meta_description }}">
    <link rel="canonical" href="{{ $canonical ?? url()->current() }}">
    <meta property="og:title" content="{{ $documentTitle }}">
    <meta property="og:description" content="{{ $description ?? $websiteSettings?->meta_description }}">
    <meta property="og:url" content="{{ $canonical ?? url()->current() }}">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta name="twitter:card" content="summary_large_image">
    @if ($websiteSettings?->featured_image_path)<meta property="og:image" content="{{ asset('storage/'.$websiteSettings->featured_image_path) }}"><meta name="twitter:image" content="{{ asset('storage/'.$websiteSettings->featured_image_path) }}">@endif
    @if ($websiteSettings?->favicon_path)<link rel="icon" href="{{ asset('storage/'.$websiteSettings->favicon_path) }}">@endif
    @if (filled($websiteSettings?->search_console_verification))
        <meta name="google-site-verification" content="{{ $websiteSettings->search_console_verification }}">
    @endif
    @if ($websiteSettings?->ga_tracking_enabled && filled($websiteSettings->ga_measurement_id))
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $websiteSettings->ga_measurement_id }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag() { dataLayer.push(arguments); }
            gtag('js', new Date());
            gtag('config', '{{ $websiteSettings->ga_measurement_id }}');
        </script>
    @endif
    <script>if (localStorage.getItem('admin-theme') === 'dark' || (!localStorage.getItem('admin-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');</script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="storefront-theme flex min-h-screen flex-col bg-[#f7f8fc] font-sans text-slate-900 antialiased transition-colors duration-200 dark:bg-[#111827] dark:text-slate-100">
    <header data-storefront-header class="sticky top-0 z-40" x-data="{ mobileMenuOpen: false, accountMenuOpen: false, cartCount: {{ collect(session('cart', []))->sum() }}, cartToastOpen: false, cartToastMessage: '', cartToastTimer: null, showCartToast(message) { this.cartToastMessage = message; this.cartToastOpen = true; window.clearTimeout(this.cartToastTimer); this.cartToastTimer = window.setTimeout(() => this.cartToastOpen = false, 2200); } }" @cart-count-updated.window="cartCount = $event.detail.count" @cart-notification.window="showCartToast($event.detail.message)" @keydown.escape.window="mobileMenuOpen = false; accountMenuOpen = false">
        <div class="border-b border-slate-200 bg-white text-slate-900 shadow-[0_8px_24px_rgba(15,23,42,.08)] transition-colors dark:border-white/10 dark:bg-[#071d32] dark:text-white dark:shadow-[0_8px_24px_rgba(2,12,27,.28)]">
            <div class="mx-auto grid min-h-[82px] max-w-[1400px] grid-cols-[auto_1fr_auto] items-center gap-3 px-4 py-3 sm:px-6 lg:gap-6 lg:px-8">
                <a href="{{ route('storefront.index') }}" class="flex min-w-0 shrink-0 items-center gap-3" aria-label="{{ $websiteSettings?->site_name ?? config('app.name') }} home">
                    @if ($websiteSettings?->logo_path)
                        <img src="{{ asset('storage/'.$websiteSettings->logo_path) }}" alt="{{ $websiteSettings->site_name }}" class="h-11 w-auto max-w-36 object-contain sm:max-w-44">
                    @else
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-[#e03090] to-[#8f1d61] text-lg font-black shadow-lg shadow-fuchsia-950/30">{{ str($websiteSettings?->site_name ?? config('app.name'))->substr(0, 1)->upper() }}</span>
                        <span class="hidden max-w-36 truncate text-lg font-extrabold tracking-tight sm:block">{{ $websiteSettings?->site_name ?? config('app.name') }}</span>
                    @endif
                </a>

                <form action="{{ route('storefront.index') }}" class="mx-auto hidden w-full max-w-2xl md:block" role="search">
                    <label for="header-search" class="sr-only">Search products</label>
                    <div class="group relative"><input id="header-search" name="search" value="{{ request('search') }}" placeholder="Search products, brands and categories" class="h-12 w-full rounded-2xl border border-slate-200 bg-white pl-5 pr-14 text-sm text-slate-900 shadow-[0_2px_8px_rgba(15,23,42,.08)] outline-none transition dark:border-white/10 dark:shadow-[0_2px_10px_rgba(0,0,0,.22)] placeholder:text-slate-400 focus:border-fuchsia-300 focus:ring-4 focus:ring-fuchsia-400/15"><button aria-label="Search" class="absolute inset-y-1.5 right-1.5 grid w-10 place-items-center rounded-xl bg-slate-950 text-white transition group-focus-within:bg-[#e03090] hover:bg-[#e03090]"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="m20 20-4-4"/></svg></button></div>
                </form>

                <nav class="ml-auto flex shrink-0 items-center gap-2">
                    <a href="{{ route('storefront.orders.track') }}" class="group hidden h-12 items-center gap-2.5 rounded-2xl border border-slate-200 bg-slate-50 dark:border-white/10 dark:bg-white/[.045] px-2.5 transition hover:border-fuchsia-300 hover:bg-fuchsia-50 dark:hover:border-fuchsia-400/40 dark:hover:bg-white/[.09] lg:flex">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-fuchsia-50 text-[#c02682] dark:bg-fuchsia-400/10 dark:text-fuchsia-300 transition group-hover:bg-[#e03090] group-hover:text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h11v10H3zM14 10h4l3 3v4h-7zM7 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/></svg></span>
                        <span class="hidden xl:block"><strong class="block whitespace-nowrap text-xs font-bold">Track Order</strong><small class="mt-0.5 block whitespace-nowrap text-[10px] text-slate-400">Check delivery</small></span>
                    </a>

                    <div class="relative hidden lg:block" @click.outside="accountMenuOpen = false">
                        <button type="button" @click="accountMenuOpen = ! accountMenuOpen" :aria-expanded="accountMenuOpen" aria-haspopup="menu" class="group flex h-12 items-center gap-2.5 rounded-2xl border border-slate-200 bg-slate-50 dark:border-white/10 dark:bg-white/[.045] px-2.5 transition hover:border-fuchsia-300 hover:bg-fuchsia-50 dark:hover:border-fuchsia-400/40 dark:hover:bg-white/[.09]">
                            @if (auth()->check() && ! auth()->user()->isAdmin() && auth()->user()->profile_image_path)<img src="{{ asset('storage/'.auth()->user()->profile_image_path) }}" alt="{{ auth()->user()->name }}" class="h-9 w-9 rounded-xl object-cover ring-1 ring-white/20">@else<span class="grid h-9 w-9 place-items-center rounded-xl bg-fuchsia-50 text-[#c02682] dark:bg-fuchsia-400/10 dark:text-fuchsia-300 transition group-hover:bg-[#e03090] group-hover:text-white"><svg class="h-[18px] w-[18px]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm-9 9a9 9 0 0 1 18 0H3Z"/></svg></span>@endif
                            <span class="hidden xl:block"><strong class="block whitespace-nowrap text-left text-xs font-bold">{{ auth()->check() ? str(auth()->user()->name)->before(' ')->limit(12) : 'Account' }}</strong><small class="mt-0.5 block whitespace-nowrap text-left text-[10px] text-slate-400">{{ auth()->check() ? (auth()->user()->isAdmin() ? 'Dashboard' : 'My Account') : 'Login or register' }}</small></span>
                            <svg class="hidden h-3.5 w-3.5 text-slate-400 transition xl:block" :class="accountMenuOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg>
                        </button>
                        <div x-cloak x-show="accountMenuOpen" x-transition class="absolute right-0 top-full z-50 mt-3 w-72 overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-900 shadow-[0_20px_55px_rgba(15,23,42,.2)] dark:border-slate-700 dark:bg-[#161f2e] dark:text-white" role="menu">
                            @auth
                                <div class="border-b border-slate-100 p-4 dark:border-slate-700"><div class="flex items-center gap-3">@if (auth()->user()->profile_image_path)<img src="{{ asset('storage/'.auth()->user()->profile_image_path) }}" alt="{{ auth()->user()->name }}" class="h-11 w-11 rounded-xl object-cover">@else<span class="grid h-11 w-11 place-items-center rounded-xl bg-[#e03090] text-sm font-bold text-white">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>@endif<div class="min-w-0"><strong class="block truncate text-sm">{{ auth()->user()->name }}</strong><span class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</span></div></div></div>
                                <div class="p-2"><a href="{{ route(auth()->user()->homeRoute()) }}" class="block rounded-xl px-3 py-2.5 text-sm font-semibold transition hover:bg-slate-100 dark:hover:bg-slate-800" role="menuitem">{{ auth()->user()->isAdmin() ? 'Dashboard' : 'My Account' }}</a>@if (auth()->user()->isAdmin())<a href="{{ route('orders.index') }}" class="block rounded-xl px-3 py-2.5 text-sm font-semibold transition hover:bg-slate-100 dark:hover:bg-slate-800" role="menuitem">Orders</a>@else<a href="{{ route('customer.dashboard') }}#recent-orders" class="block rounded-xl px-3 py-2.5 text-sm font-semibold transition hover:bg-slate-100 dark:hover:bg-slate-800" role="menuitem">My Orders</a><a href="{{ route('customer.reviews.index') }}" class="block rounded-xl px-3 py-2.5 text-sm font-semibold transition hover:bg-slate-100 dark:hover:bg-slate-800" role="menuitem">My Reviews</a>@endif<a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2.5 text-sm font-semibold transition hover:bg-slate-100 dark:hover:bg-slate-800" role="menuitem">View Profile</a>@unless (auth()->user()->isAdmin())<a href="{{ route('customer.password.edit') }}" class="block rounded-xl px-3 py-2.5 text-sm font-semibold transition hover:bg-slate-100 dark:hover:bg-slate-800" role="menuitem">Password &amp; Security</a>@endunless</div>
                                <div class="border-t border-slate-100 p-2 dark:border-slate-700"><form method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-rose-600 transition hover:bg-rose-50 dark:hover:bg-rose-500/10" role="menuitem">Logout</button></form></div>
                            @else
                                <div class="p-5"><strong class="block text-base">Welcome to {{ $websiteSettings?->site_name ?? config('app.name') }}</strong><p class="mt-1.5 text-xs leading-5 text-slate-500 dark:text-slate-400">Sign in to manage orders, reviews and account details.</p><a href="{{ route('login') }}" class="mt-4 flex h-11 items-center justify-center rounded-xl bg-[#e03090] text-sm font-bold text-white" role="menuitem">Login</a><a href="{{ route('register') }}" class="mt-2 flex h-11 items-center justify-center rounded-xl border border-slate-200 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800" role="menuitem">Create Account</a></div>
                            @endauth
                        </div>
                    </div>

                    <button type="button" @click="toggleTheme()" :aria-label="darkMode ? 'Switch to light mode' : 'Switch to dark mode'" :title="darkMode ? 'Light mode' : 'Dark mode'" class="grid h-12 w-12 place-items-center rounded-2xl border border-slate-200 bg-slate-50 dark:border-white/10 dark:bg-white/[.045] text-slate-600 transition hover:border-fuchsia-300 hover:bg-fuchsia-50 hover:text-[#c02682] dark:hover:border-fuchsia-400/40 dark:hover:bg-white/[.09] dark:hover:text-white"><svg x-cloak x-show="darkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="4" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg><svg x-cloak x-show="!darkMode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.35 15.35A9 9 0 0 1 8.65 3.65a9 9 0 1 0 11.7 11.7Z"/></svg></button>
                    <a href="{{ route('cart.index') }}" x-bind:aria-label="`View cart with ${cartCount} items`" class="group flex h-12 items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 dark:border-white/10 dark:bg-white/[.045] p-1.5 pr-2.5 transition hover:border-fuchsia-300 hover:bg-fuchsia-50 dark:hover:border-fuchsia-400/40 dark:hover:bg-white/[.09]"><span class="grid h-9 w-9 place-items-center rounded-xl bg-fuchsia-50 text-[#c02682] dark:bg-fuchsia-400/10 dark:text-fuchsia-300 transition group-hover:bg-[#e03090] group-hover:text-white"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l2 11h10l3-8H6m2 12a1 1 0 1 0 0 2 1 1 0 0 0 0-2Zm9 0a1 1 0 1 0 4 0 1 1 0 0 0-4 0Z"/></svg></span><span class="hidden text-xs font-bold xl:inline">Cart</span><span class="grid min-h-5 min-w-5 place-items-center rounded-full bg-[#e03090] px-1.5 text-[10px] font-black text-white" x-text="cartCount">{{ collect(session('cart', []))->sum() }}</span></a>
                    <button type="button" class="grid h-12 w-12 place-items-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-700 transition dark:border-white/10 dark:bg-white/[.045] dark:text-white hover:border-fuchsia-300 hover:bg-fuchsia-50 dark:hover:border-fuchsia-400/40 dark:hover:bg-white/[.09] lg:hidden" aria-controls="mobile-store-menu" x-bind:aria-expanded="mobileMenuOpen" @click="mobileMenuOpen = true"><span class="sr-only">Open menu</span><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.8" d="M4 7h16M4 12h16M4 17h16"/></svg></button>
                </nav>

                <form action="{{ route('storefront.index') }}" class="col-span-3 md:hidden" role="search"><label for="mobile-header-search" class="sr-only">Search products</label><div class="relative"><input id="mobile-header-search" name="search" value="{{ request('search') }}" placeholder="Search products..." class="h-11 w-full rounded-xl border border-slate-200 bg-white pl-4 pr-12 text-sm text-slate-900 shadow-[0_2px_8px_rgba(15,23,42,.08)] dark:border-white/10 dark:shadow-[0_2px_10px_rgba(0,0,0,.22)] focus:ring-4 focus:ring-fuchsia-400/20"><button aria-label="Search" class="absolute inset-y-0 right-0 grid w-12 place-items-center text-slate-900"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="m20 20-4-4"/></svg></button></div></form>
            </div>
        </div>

        <div class="border-b border-slate-200/80 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-[#111827]/95">
            <nav class="mx-auto hidden min-h-12 max-w-[1400px] items-center gap-7 overflow-visible px-4 text-[13px] font-semibold text-slate-600 dark:text-slate-300 sm:px-6 lg:flex lg:px-8">
                @foreach ($storeNavigationCategories as $navigationCategory)
                    <div class="group relative shrink-0"><a href="{{ route('catalog.show', $navigationCategory->slug) }}" class="relative flex min-h-12 items-center gap-1.5 transition after:absolute after:inset-x-0 after:bottom-0 after:h-0.5 after:origin-center after:rounded-full after:bg-[#e03090] after:transition-transform {{ request()->route('slug') === $navigationCategory->slug ? 'text-slate-950 after:scale-x-100 dark:text-white' : 'after:scale-x-0 hover:text-slate-950 group-hover:after:scale-x-100 dark:hover:text-white' }}">{{ $navigationCategory->name }}@if ($navigationCategory->childrenRecursive->isNotEmpty())<svg class="h-3 w-3 text-slate-400 transition group-hover:rotate-180 group-hover:text-[#e03090]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg>@endif</a>@if ($navigationCategory->childrenRecursive->isNotEmpty())<div class="invisible absolute left-0 top-full z-50 w-64 origin-top translate-y-1 pt-2 opacity-0 transition duration-200 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100 group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100"><div class="rounded-2xl border border-slate-200/80 bg-white p-2 shadow-[0_16px_40px_rgba(15,23,42,.16)] dark:border-slate-700 dark:bg-[#161f2e]">@foreach ($navigationCategory->childrenRecursive as $subCategory)<x-storefront-subcategory-link :category="$subCategory" />@endforeach</div></div>@endif</div>
                @endforeach
            </nav>
        </div>

        <div x-cloak x-show="mobileMenuOpen" class="fixed inset-0 z-[60] lg:hidden" role="dialog" aria-modal="true" aria-label="Store menu"><div x-show="mobileMenuOpen" x-transition.opacity class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" @click="mobileMenuOpen = false"></div><aside id="mobile-store-menu" x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full" class="absolute inset-y-0 right-0 flex w-[min(90vw,380px)] flex-col bg-white text-slate-900 shadow-2xl dark:bg-[#111827] dark:text-white"><div class="flex items-center justify-between border-b border-slate-200 p-5 dark:border-slate-800"><strong class="truncate">{{ $websiteSettings?->site_name ?? config('app.name') }}</strong><button type="button" @click="mobileMenuOpen = false" class="grid h-10 w-10 place-items-center rounded-xl bg-slate-100 dark:bg-slate-800"><span class="sr-only">Close menu</span><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.8" d="m6 6 12 12M18 6 6 18"/></svg></button></div><nav class="flex-1 overflow-y-auto p-3" x-data="{ openCategory: null }"><a href="{{ route('storefront.orders.track') }}" class="mb-2 block rounded-xl bg-fuchsia-50 px-4 py-3 text-sm font-bold text-[#a91f70] dark:bg-fuchsia-500/10 dark:text-fuchsia-300">Track Order</a><a href="{{ auth()->check() ? route(auth()->user()->homeRoute()) : route('login') }}" class="mb-3 block rounded-xl bg-slate-100 px-4 py-3 text-sm font-bold dark:bg-slate-800">{{ auth()->check() ? (auth()->user()->isAdmin() ? 'Dashboard' : 'My Account') : 'Login / Register' }}</a>@foreach ($storeNavigationCategories as $navigationCategory)<div class="mb-1 rounded-xl border border-slate-200 dark:border-slate-700"><div class="flex items-center"><a href="{{ route('catalog.show', $navigationCategory->slug) }}" class="min-w-0 flex-1 px-4 py-3 text-sm font-semibold">{{ $navigationCategory->name }}</a>@if ($navigationCategory->childrenRecursive->isNotEmpty())<button type="button" @click="openCategory = openCategory === {{ $navigationCategory->id }} ? null : {{ $navigationCategory->id }}" class="grid h-11 w-11 place-items-center text-slate-400"><svg class="h-4 w-4 transition" :class="openCategory === {{ $navigationCategory->id }} && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m6 9 6 6 6-6"/></svg></button>@endif</div>@if ($navigationCategory->childrenRecursive->isNotEmpty())<div x-cloak x-show="openCategory === {{ $navigationCategory->id }}" class="border-t border-slate-200 p-2 dark:border-slate-700">@foreach ($navigationCategory->childrenRecursive as $subCategory)<a href="{{ route('catalog.show', $subCategory->slug) }}" class="block rounded-lg px-3 py-2 text-sm text-slate-500 hover:bg-slate-100 dark:text-slate-400 dark:hover:bg-slate-800">{{ $subCategory->name }}</a>@endforeach</div>@endif</div>@endforeach</nav><div class="border-t border-slate-200 p-4 dark:border-slate-800"><a href="{{ route('cart.index') }}" class="flex items-center justify-between rounded-xl bg-[#e03090] px-4 py-3 text-sm font-bold text-white"><span>View Cart</span><span><span x-text="cartCount">{{ collect(session('cart', []))->sum() }}</span> items</span></a></div></aside></div>

        <div x-cloak x-show="cartToastOpen" x-transition class="fixed right-4 top-24 z-[70] flex max-w-[calc(100vw-2rem)] items-center gap-3 rounded-2xl border border-emerald-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800 shadow-[0_16px_45px_rgba(15,23,42,.2)]" role="status" aria-live="polite"><span class="grid h-7 w-7 place-items-center rounded-full bg-emerald-500 text-white">&#10003;</span><span x-text="cartToastMessage"></span><button type="button" @click="cartToastOpen = false" class="ml-2 text-lg text-slate-400" aria-label="Close notification">&times;</button></div>
    </header>
    <main class="flex-1">{{ $slot }}</main>
    <footer>
        <div class="bg-gradient-to-br from-[#04192d] via-[#062b4b] to-[#071724] text-white">
            <div class="mx-auto grid w-full max-w-[1400px] lg:grid-cols-[.8fr_1.2fr]">
                <div class="border-b border-white/10 px-4 py-12 sm:px-6 sm:py-16 lg:border-b-0 lg:border-r lg:px-8 lg:py-20">
                    <p class="max-w-md text-4xl font-semibold leading-[.98] tracking-[-.035em] sm:text-5xl">Find something<br>you'll love.</p>
                    <p class="mt-7 max-w-md text-sm leading-7 text-slate-300 sm:text-base">{{ $websiteSettings?->meta_description ?: 'Thoughtfully selected products, simple ordering, and dependable delivery&mdash;all in one place.' }}</p>
                    <a href="{{ route('storefront.index') }}#latest-products" class="mt-9 inline-flex items-center gap-3 rounded-full border border-white/30 px-5 py-3 text-sm font-semibold transition hover:border-white hover:bg-white hover:text-[#06233f]">Shop collection <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m9 18 6-6-6-6"/></svg></a>
                </div>

                <div class="px-4 pb-8 pt-12 sm:px-6 sm:pb-10 sm:pt-16 lg:px-8 lg:pb-8 lg:pt-20">
                    <div class="grid gap-10 sm:grid-cols-2 xl:grid-cols-[.8fr_.8fr_1.4fr]">
                        <div>
                            <h2 class="text-2xl font-semibold tracking-tight">Shop</h2>
                            <nav class="mt-5 grid gap-3 text-sm text-slate-300">
                                <a href="{{ route('storefront.index') }}#latest-products" class="transition hover:text-white">Latest products</a>
                                <a href="{{ route('storefront.index') }}#shop-categories" class="transition hover:text-white">Categories</a>
                                <a href="{{ route('storefront.blog.index') }}" class="transition hover:text-white">Blog</a>
                                <a href="{{ route('cart.index') }}" class="transition hover:text-white">Your cart</a>
                                <a href="{{ route('storefront.orders.track') }}" class="transition hover:text-white">Track order</a>
                            </nav>
                        </div>
                        <div>
                            <h2 class="text-2xl font-semibold tracking-tight">Account</h2>
                            <nav class="mt-5 grid gap-3 text-sm text-slate-300">
                                <a href="{{ auth()->check() ? route(auth()->user()->homeRoute()) : route('login') }}" class="transition hover:text-white">{{ auth()->check() ? (auth()->user()->isAdmin() ? 'Dashboard' : 'My account') : 'Sign in' }}</a>
                                @guest<a href="{{ route('register') }}" class="transition hover:text-white">Create account</a>@endguest
                                <a href="{{ route('cart.index') }}" class="transition hover:text-white">Checkout</a>
                            </nav>
                        </div>
                        <div class="sm:col-span-2 xl:col-span-1">
                            <p class="text-2xl font-semibold leading-tight tracking-tight">Fresh finds,<br>straight to your inbox.</p>
                            <p class="mt-4 max-w-sm text-sm leading-6 text-slate-300">Subscribe for product updates, new arrivals, and special offers.</p>
                            <form method="POST" action="{{ route('newsletter.subscribe') }}" class="mt-6 max-w-md">
                                @csrf
                                <div class="flex items-center rounded-2xl bg-white/[.07] p-1.5 ring-1 ring-white/10 focus-within:ring-white/30">
                                    <label for="footer-email" class="sr-only">Email address</label>
                                    <input id="footer-email" name="email" type="email" required value="{{ old('email') }}" placeholder="Enter your email" class="min-w-0 flex-1 border-0 bg-transparent px-4 py-3 text-sm text-white placeholder:text-slate-400 focus:ring-0">
                                    <button type="submit" aria-label="Subscribe" class="grid h-11 w-11 shrink-0 place-items-center rounded-xl text-white transition hover:bg-white hover:text-[#06233f]"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="m22 2-7 20-4-9-9-4 20-7Z"/><path stroke-linecap="round" stroke-width="1.7" d="M22 2 11 13"/></svg></button>
                                </div>
                                @error('email')<p class="mt-2 text-xs font-medium text-rose-300">{{ $message }}</p>@enderror
                                @if (session('newsletterStatus'))
                                    <p class="mt-2 text-xs font-medium text-emerald-300">{{ session('newsletterStatus') }}</p>
                                @endif
                            </form>
                        </div>
                    </div>

                    <div class="mt-12 grid items-center gap-4 rounded-2xl bg-white/[.035] px-6 py-8 text-center sm:px-8 xl:grid-cols-[auto_auto_auto] xl:justify-start xl:gap-6 xl:text-left">
                        <span class="mx-auto grid h-14 w-14 place-items-center text-white xl:mx-0">
                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5" stroke-width="1.5"/></svg>
                        </span>
                        <div>
                            <h2 class="text-lg font-semibold">Dhaka Office</h2>
                            <address class="mt-2 text-sm not-italic leading-6 text-slate-300">House 08, Road 13/A, Sector 06, Uttara, Dhaka-1230</address>
                        </div>
                        <a href="tel:+8801736741793" class="whitespace-nowrap text-xl font-extrabold text-white transition hover:text-sky-300">+88 01736-741793</a>
                    </div>

                    <div class="mt-14 flex flex-col gap-5 border-t border-white/10 pt-7 text-sm text-slate-400 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('storefront.index') }}" class="inline-flex items-center gap-3 text-white">
                            @if ($websiteSettings?->logo_path)
                                <img src="{{ asset('storage/'.$websiteSettings->logo_path) }}" alt="{{ $websiteSettings->site_name }}" class="h-9 w-auto max-w-36 object-contain brightness-0 invert">
                            @else
                                <span class="grid h-9 w-9 place-items-center rounded-lg bg-white text-sm font-black text-[#06233f]">{{ str($websiteSettings?->site_name ?? config('app.name'))->substr(0, 1)->upper() }}</span>
                                <span class="font-bold">{{ $websiteSettings?->site_name ?? config('app.name') }}</span>
                            @endif
                        </a>
                        <p>&copy; {{ now()->year }} {{ $websiteSettings?->site_name ?? config('app.name') }}. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
