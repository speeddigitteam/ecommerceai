@props(['category', 'selectedCategoryIds' => [], 'depth' => 0])

<label class="flex cursor-pointer items-center gap-2 rounded-md py-1.5 pr-2 text-sm hover:bg-slate-50 dark:hover:bg-slate-800" style="padding-left: {{ 0.5 + ($depth * 1.1) }}rem">
    <input name="category_ids[]" type="checkbox" value="{{ $category->id }}" @checked(in_array($category->id, $selectedCategoryIds)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
    <span class="{{ $depth === 0 ? 'font-medium' : '' }}">{{ $category->name }}</span>
</label>

@foreach ($category->childrenRecursive as $child)
    <x-product-category-checkbox :category="$child" :selected-category-ids="$selectedCategoryIds" :depth="$depth + 1" />
@endforeach
