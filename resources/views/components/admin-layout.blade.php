@props(['title' => 'Shopwise Admin'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="adminChrome()" x-init="init()" :class="{ dark: darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} | {{ $websiteSettings?->site_name ?? config('app.name', 'Laravel') }}</title>
    @if ($websiteSettings?->meta_description)<meta name="description" content="{{ $websiteSettings->meta_description }}">@endif
    @if ($websiteSettings?->meta_keywords)<meta name="keywords" content="{{ $websiteSettings->meta_keywords }}">@endif
    @if ($websiteSettings?->featured_image_path)<meta property="og:image" content="{{ asset('storage/'.$websiteSettings->featured_image_path) }}"><meta name="twitter:image" content="{{ asset('storage/'.$websiteSettings->featured_image_path) }}">@endif
    @if ($websiteSettings?->favicon_path)<link rel="icon" href="{{ asset('storage/'.$websiteSettings->favicon_path) }}">@endif
    <script>
        if (localStorage.getItem('admin-theme') === 'dark' || (!localStorage.getItem('admin-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="ds-admin font-sans antialiased">
    {{ $slot }}
    {{ $scripts ?? '' }}
</body>
</html>
