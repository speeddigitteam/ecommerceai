@props(['title' => 'My Account'])

<x-storefront-layout :title="$title">
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 text-center sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="flex items-center justify-center gap-2 text-sm text-[#315279]">
                <a href="{{ route('storefront.index') }}" aria-label="Home" class="text-sky-500 transition hover:text-sky-600"><svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3 2.5 11h2.2v9h5.5v-5.5h3.6V20h5.5v-9h2.2L12 3Z"/></svg></a>
                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m9 18 6-6-6-6"/></svg>
                <a href="{{ route('customer.dashboard') }}" class="hover:text-sky-600">Customer</a>
                <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m9 18 6-6-6-6"/></svg>
                <span class="font-medium text-[#08264b]">{{ request()->routeIs('customer.dashboard') ? 'Dashboard' : $title }}</span>
            </nav>
            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-[#061d3c]">{{ request()->routeIs('customer.dashboard') ? 'Customer Dashboard' : $title }}</h1>
        </div>
    </section>
    <div class="mx-auto grid max-w-7xl items-start gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[18rem_minmax(0,1fr)] lg:px-8">
        <aside class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="relative h-32 overflow-hidden bg-[#0b1830]">
                <div class="absolute inset-0 opacity-20" style="background-image:radial-gradient(circle at 20% 30%,#93c5fd 0 1px,transparent 2px),radial-gradient(circle at 75% 65%,#93c5fd 0 1px,transparent 2px);background-size:28px 28px,34px 34px"></div>
                <svg class="absolute -left-3 top-5 h-20 w-20 text-slate-500/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1" d="M3 12h4l2-5 3 10 2-5h7"/><circle cx="12" cy="12" r="10"/></svg>
                <svg class="absolute right-4 top-4 h-20 w-20 text-slate-500/25" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1" d="M4 19h16M7 16V8l5-4 5 4v8M9 11h6M9 14h6"/></svg>
            </div>
            <div class="relative px-4 pb-5">
                <div class="-mt-16 text-center">
                    <div class="relative mx-auto w-fit rounded-full border-4 border-white bg-white shadow-sm">
                        @if(auth()->user()->profile_image_path)<img src="{{ asset('storage/'.auth()->user()->profile_image_path) }}" alt="{{ auth()->user()->name }}" class="h-28 w-28 rounded-full object-cover">@else<div class="grid h-28 w-28 place-items-center rounded-full bg-gradient-to-br from-sky-500 to-blue-700 text-3xl font-bold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>@endif
                        <span class="absolute bottom-1 right-1 grid h-5 w-5 place-items-center rounded-full border-2 border-white bg-emerald-500 text-[9px] font-black text-white">✓</span>
                    </div>
                    <h2 class="mt-4 truncate px-2 text-lg font-bold text-[#08264b]">{{ auth()->user()->name }}</h2>
                    <p class="mt-1 truncate text-xs text-slate-500">Customer · {{ auth()->user()->email }}</p>
                </div>
                @php($menuLink = 'flex items-center gap-3 rounded-lg px-3 py-3 text-[13px] font-medium transition')
                <nav class="mt-7 space-y-1">
                    <a href="{{ route('customer.dashboard') }}" class="{{ $menuLink }} {{ request()->routeIs('customer.dashboard') ? 'bg-[#1685f8] text-white shadow-sm' : 'text-[#17365d] hover:bg-slate-50' }}"><svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="6" height="6" rx="1" stroke-width="1.8"/><rect x="15" y="3" width="6" height="6" rx="1" stroke-width="1.8"/><rect x="3" y="15" width="6" height="6" rx="1" stroke-width="1.8"/><rect x="15" y="15" width="6" height="6" rx="1" stroke-width="1.8"/></svg><span>Dashboard</span></a>
                    <a href="{{ route('customer.dashboard') }}#recent-orders" class="{{ $menuLink }} text-[#17365d] hover:bg-slate-50"><svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 7h12l1 13H5L6 7Zm3 0a3 3 0 0 1 6 0"/></svg><span>My Orders</span></a>
                    <a href="{{ route('customer.reviews.index') }}" class="{{ $menuLink }} {{ request()->routeIs('customer.reviews.*') ? 'bg-[#1685f8] text-white shadow-sm' : 'text-[#17365d] hover:bg-slate-50' }}"><svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m12 3 2.5 5.1 5.6.8-4 3.9.9 5.5-5-2.6-5 2.6.9-5.5-4-3.9 5.6-.8L12 3Z"/></svg><span>Reviews</span></a>
                    <a href="{{ route('profile.edit') }}" class="{{ $menuLink }} {{ request()->routeIs('profile.*') ? 'bg-[#1685f8] text-white shadow-sm' : 'text-[#17365d] hover:bg-slate-50' }}"><svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M5 20a7 7 0 0 1 14 0"/></svg><span>Profile Settings</span></a>
                    <a href="{{ route('profile.edit') }}" class="{{ $menuLink }} text-[#17365d] hover:bg-slate-50"><svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 20h4l11-11a2.8 2.8 0 0 0-4-4L4 16v4Z"/><path stroke-width="1.8" d="m13.5 6.5 4 4"/></svg><span>Edit Profile</span></a>
                    <a href="{{ route('customer.password.edit') }}" class="{{ $menuLink }} {{ request()->routeIs('customer.password.edit') ? 'bg-[#1685f8] text-white shadow-sm' : 'text-[#17365d] hover:bg-slate-50' }}"><svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2" stroke-width="1.8"/><path stroke-linecap="round" stroke-width="1.8" d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><span>Change Password</span></a>
                    <a href="{{ route('storefront.shop') }}" class="{{ $menuLink }} text-[#17365d] hover:bg-slate-50"><svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l2 11h10l3-8H6m2 12h.01M17 18h.01"/></svg><span>Continue Shopping</span></a>
                </nav>
                <form method="POST" action="{{ route('logout') }}" class="mt-1">@csrf<button class="{{ $menuLink }} w-full text-[#17365d] hover:bg-rose-50 hover:text-rose-600"><svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="1.8" d="M10 17l5-5-5-5m5 5H3m9-9h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/></svg><span>Logout</span></button></form>
            </div>
        </aside>
        <div class="min-w-0">{{ $slot }}</div>
    </div>
</x-storefront-layout>
