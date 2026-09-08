@props(['category', 'depth' => 0, 'selectedIds' => []])
<label class="flex items-center gap-2 text-sm" style="padding-left: {{ $depth * 1.1 }}rem"><input type="checkbox" name="category_ids[]" value="{{ $category->id }}" @checked(in_array($category->id, $selectedIds)) class="rounded border-slate-300 text-indigo-600"><span>{{ $category->name }}</span></label>
@foreach($category->childrenRecursive as $child)<x-blog-category-checkbox :category="$child" :depth="$depth + 1" :selected-ids="$selectedIds" />@endforeach
