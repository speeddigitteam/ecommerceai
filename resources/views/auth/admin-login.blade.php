<x-guest-layout title="Admin Login">
    <div class="mb-7 text-center">
        <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M8 10V7a4 4 0 0 1 8 0v3"/></svg></span>
        <h1 class="mt-4 text-2xl font-bold tracking-tight">Admin login</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Sign in with an administrator account.</p>
    </div>

    <x-auth-session-status class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm dark:bg-emerald-500/15" :status="session('status')" />

    <form method="POST" action="{{ route('admin.login.store') }}" x-data="{ showPassword: false }" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="text-sm font-semibold">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="mt-2 w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
            @error('email')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <div class="flex items-center justify-between gap-3"><label for="password" class="text-sm font-semibold">Password</label><a href="{{ route('password.request') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Forgot password?</a></div>
            <div class="relative mt-2"><input id="password" name="password" :type="showPassword ? 'text' : 'password'" required autocomplete="current-password" class="w-full rounded-xl border-slate-300 bg-white px-3 py-3 pr-11 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"><button type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5" stroke-width="2"/></svg></button></div>
            @error('password')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <x-recaptcha-widget />

        <label for="remember_me" class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-600 dark:text-slate-400"><input id="remember_me" name="remember" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800">Remember me</label>
        <button class="flex w-full items-center justify-center rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-700">Sign in to admin</button>
    </form>
</x-guest-layout>
