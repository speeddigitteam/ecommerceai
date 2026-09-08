@props(['category', 'depth' => 0, 'selectedId' => null, 'excludedIds' => []])

@unless (in_array($category->id, $excludedIds, true))
    <option value="{{ $category->id }}" @selected((string) $selectedId === (string) $category->id)>{{ str_repeat('— ', $depth) }}{{ $category->name }}</option>
    @foreach ($category->childrenRecursive as $child)
        <x-blog-category-option :category="$child" :depth="$depth + 1" :selected-id="$selectedId" :excluded-ids="$excludedIds" />
    @endforeach
@endunless
