<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @php
        $itemName = $type === 'Product' ? $item->title : $item->name;
        $pageTitle = $type === 'Product' ? ($item->seo_title ?: $itemName) : $itemName;
        $metaDescription = $type === 'Product'
            ? ($item->meta_description ?: $item->short_description ?: strip_tags($item->description ?? '') ?: $websiteSettings?->meta_description)
            : ($item->description ?? $websiteSettings?->meta_description ?? $itemName);
        $siteName = $websiteSettings?->site_name ?? config('app.name');
        $socialImage = $type === 'Product' && $item->featured_image_path
            ? asset('storage/'.$item->featured_image_path)
            : ($websiteSettings?->featured_image_path ? asset('storage/'.$websiteSettings->featured_image_path) : null);
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }} | {{ $siteName }}</title>
    @if ($metaDescription)<meta name="description" content="{{ $metaDescription }}">@endif
    <link rel="canonical" href="{{ route('catalog.show', $item->slug) }}">
    <meta property="og:title" content="{{ $pageTitle }} | {{ $siteName }}">
    @if ($metaDescription)<meta property="og:description" content="{{ $metaDescription }}">@endif
    <meta property="og:url" content="{{ route('catalog.show', $item->slug) }}">
    <meta property="og:type" content="{{ $type === 'Product' ? 'product' : 'website' }}">
    @if ($socialImage)<meta property="og:image" content="{{ $socialImage }}">@endif
    <meta name="twitter:card" content="{{ $socialImage ? 'summary_large_image' : 'summary' }}">
    @if ($websiteSettings?->favicon_path)<link rel="icon" href="{{ asset('storage/'.$websiteSettings->favicon_path) }}">@endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased">
    <header class="border-b border-slate-200 bg-white"><div class="mx-auto flex max-w-6xl items-center justify-between px-5 py-5">@if ($websiteSettings?->logo_path)<img src="{{ asset('storage/'.$websiteSettings->logo_path) }}" alt="{{ $websiteSettings->site_name }}" class="h-10 w-auto max-w-48 object-contain">@else<a href="/" class="text-xl font-bold">{{ $websiteSettings?->site_name ?? config('app.name') }}</a>@endif<a href="/" class="text-sm font-semibold text-indigo-600">Home</a></div></header>
    <main class="mx-auto max-w-6xl px-5 py-12 sm:py-20">
        <p class="text-sm font-semibold uppercase tracking-wider text-indigo-600">{{ $type }}</p>
        <h1 class="mt-3 text-4xl font-extrabold tracking-tight sm:text-5xl">{{ $itemName }}</h1>
        @if ($type === 'Category' && $item->description)<div class="prose prose-slate mt-7 max-w-3xl text-slate-600">{!! $item->description !!}</div>@endif
        @if ($type === 'Product' && $item->description)<div class="prose prose-slate mt-7 max-w-3xl text-slate-600">{!! $item->description !!}</div>@endif
        @if ($type === 'Category' && $item->thumbnail_path)<img src="{{ asset('storage/'.$item->thumbnail_path) }}" alt="{{ $itemName }}" class="mt-10 max-h-96 w-full max-w-3xl rounded-3xl object-cover">@endif
        @if ($type === 'Product' && $item->featured_image_path)<img src="{{ asset('storage/'.$item->featured_image_path) }}" alt="{{ $itemName }}" class="mt-10 max-h-96 w-full max-w-3xl rounded-3xl object-cover">@endif
    </main>
</body>
</html>
