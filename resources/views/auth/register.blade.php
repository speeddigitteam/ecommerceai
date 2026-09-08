<x-guest-layout title="Register">
    <div class="mb-7 text-center">
        <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-300"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M4 21a8 8 0 0 1 16 0m5-13v6m-3-3h6"/></svg></span>
        <h1 class="mt-4 text-2xl font-bold tracking-tight">Create your account</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Enter your details to get started.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" x-data="{ showPassword: false, showConfirmation: false }" class="space-y-5">
        @csrf
        <div>
            <label for="name" class="text-sm font-semibold">Full name</label>
            <div class="relative mt-2"><svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M4 21a8 8 0 0 1 16 0"/></svg><input id="name" name="name" type="text" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="Your full name" class="w-full rounded-xl border-slate-300 bg-white py-3 pl-11 pr-3 text-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></div>
            @error('name')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="email" class="text-sm font-semibold">Email address</label>
            <div class="relative mt-2"><svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m4 7 8 6 8-6"/></svg><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="username" placeholder="you@example.com" class="w-full rounded-xl border-slate-300 bg-white py-3 pl-11 pr-3 text-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"></div>
            @error('email')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div><label for="password" class="text-sm font-semibold">Password</label><div class="relative mt-2"><input id="password" name="password" :type="showPassword ? 'text' : 'password'" required autocomplete="new-password" placeholder="Min. 8 characters" class="w-full rounded-xl border-slate-300 bg-white py-3 pl-3 pr-10 text-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"><button type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5" stroke-width="2"/></svg></button></div>@error('password')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror</div>
            <div><label for="password_confirmation" class="text-sm font-semibold">Confirm password</label><div class="relative mt-2"><input id="password_confirmation" name="password_confirmation" :type="showConfirmation ? 'text' : 'password'" required autocomplete="new-password" placeholder="Repeat password" class="w-full rounded-xl border-slate-300 bg-white py-3 pl-3 pr-10 text-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"><button type="button" @click="showConfirmation = !showConfirmation" :aria-label="showConfirmation ? 'Hide password' : 'Show password'" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-indigo-600"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5" stroke-width="2"/></svg></button></div></div>
        </div>

        <button class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-700">Create account <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="m9 18 6-6-6-6"/></svg></button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">Already have an account? <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Sign in</a></p>
</x-guest-layout>
