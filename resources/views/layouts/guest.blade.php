@props(['title' => 'Welcome'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $title }} | {{ $websiteSettings?->site_name ?? config('app.name', 'Laravel') }}</title>
        @if ($websiteSettings?->meta_description)<meta name="description" content="{{ $websiteSettings->meta_description }}">@endif
        @if ($websiteSettings?->favicon_path)<link rel="icon" href="{{ asset('storage/'.$websiteSettings->favicon_path) }}">@endif
        <script>if (localStorage.getItem('admin-theme') === 'dark' || (!localStorage.getItem('admin-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) document.documentElement.classList.add('dark');</script>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="font-sans antialiased">
        <main class="relative grid min-h-screen place-items-center overflow-hidden bg-slate-50 px-4 py-10 text-slate-900 dark:bg-[#0f172a] dark:text-slate-100">
            <div class="pointer-events-none absolute -left-32 -top-32 h-96 w-96 rounded-full bg-indigo-200/50 blur-3xl dark:bg-indigo-600/10"></div>
            <div class="pointer-events-none absolute -bottom-40 -right-32 h-96 w-96 rounded-full bg-violet-200/50 blur-3xl dark:bg-violet-600/10"></div>
            <div class="relative w-full max-w-md">
                <a href="/" class="mx-auto mb-7 flex w-fit justify-center">
                    @if ($websiteSettings?->logo_path)
                        <img src="{{ asset('storage/'.$websiteSettings->logo_path) }}" alt="{{ $websiteSettings->site_name }}" class="h-12 w-auto max-w-56 object-contain">
                    @else
                        <span class="text-2xl font-extrabold tracking-tight">{{ $websiteSettings?->site_name ?? config('app.name', 'Laravel') }}</span>
                    @endif
                </a>
                <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-2xl shadow-slate-200/60 dark:border-slate-700 dark:bg-[#161f2e] dark:shadow-black/20 sm:p-8">
                    {{ $slot }}
                </section>
                <p class="mt-6 text-center text-xs text-slate-400">© {{ now()->year }} {{ $websiteSettings?->site_name ?? config('app.name', 'Laravel') }}. All rights reserved.</p>
            </div>
        </main>
    </body>
</html>
