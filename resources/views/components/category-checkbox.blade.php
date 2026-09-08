@props(['category', 'depth' => 0])

<label class="flex cursor-pointer items-center gap-3 rounded-lg px-2 py-2 hover:bg-slate-50 dark:hover:bg-slate-800" style="padding-left: {{ 0.5 + ($depth * 1.25) }}rem">
    <input type="checkbox" value="{{ $category->id }}" x-model="selectedCategories" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
    <span class="{{ $depth === 0 ? 'font-medium' : '' }}">{{ $category->name }}</span>
</label>

@foreach ($category->childrenRecursive as $child)
    <x-category-checkbox :category="$child" :depth="$depth + 1" />
@endforeach
