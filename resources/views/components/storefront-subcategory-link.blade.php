@props(['category', 'depth' => 0])

<a href="{{ route('catalog.show', $category->slug) }}" class="block rounded-xl px-3 py-2.5 text-[13px] font-normal leading-5 text-slate-700 transition duration-150 hover:bg-slate-100 hover:text-slate-950" style="padding-left: {{ 0.75 + ($depth * 1.1) }}rem">
    <span class="truncate">{{ $category->name }}</span>
</a>

@foreach ($category->childrenRecursive as $childCategory)
    <x-storefront-subcategory-link :category="$childCategory" :depth="$depth + 1" />
@endforeach
