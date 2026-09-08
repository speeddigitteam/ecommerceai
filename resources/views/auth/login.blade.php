<x-guest-layout title="Login">
    <div class="mb-7 text-center">
        <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
        <h1 class="mt-4 text-2xl font-bold tracking-tight">Customer login</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Sign in to manage your storefront account.</p>
    </div>

    <x-auth-session-status class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm dark:bg-emerald-500/15" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" x-data="{ showPassword: false }" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="text-sm font-semibold">Email address</label>
            <div class="relative mt-2"><svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4 7 8 6 8-6"/></svg><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@example.com" class="w-full rounded-xl border-slate-300 bg-white py-3 pl-11 pr-3 text-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></div>
            @error('email')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <div class="flex items-center justify-between gap-3"><label for="password" class="text-sm font-semibold">Password</label>@if (Route::has('password.request'))<a href="{{ route('password.request') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Forgot password?</a>@endif</div>
            <div class="relative mt-2"><svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><input id="password" name="password" :type="showPassword ? 'text' : 'password'" required autocomplete="current-password" placeholder="Enter your password" class="w-full rounded-xl border-slate-300 bg-white py-3 pl-11 pr-11 text-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"><button type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-indigo-600"><svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5" stroke-width="2"/></svg><svg x-cloak x-show="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m4 4 16 16M10.6 6.2A10 10 0 0 1 12 6c6.5 0 10 6 10 6a15 15 0 0 1-3 3.7M6.1 6.1C3.4 8 2 12 2 12s3.5 6 10 6a10 10 0 0 0 3-.4"/></svg></button></div>
            @error('password')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <x-recaptcha-widget />

        <label for="remember_me" class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600 dark:text-slate-400"><input id="remember_me" name="remember" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800">Remember me</label>
        <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900">Sign in <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m9 18 6-6-6-6"/></svg></button>
    </form>

    @if (Route::has('register'))<p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">Don't have an account? <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Create account</a></p>@endif
</x-guest-layout>
