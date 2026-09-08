<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Services\MediaLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BlogCategoryController extends Controller
{
    public function __construct(private readonly MediaLibrary $mediaLibrary) {}

    public function index(): View
    {
        return view('blog.categories', [
            'categories' => BlogCategory::query()
                ->with('childrenRecursive')
                ->whereNull('parent_id')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function show(BlogCategory $blogCategory): View
    {
        return view('blog.categories-show', ['category' => $blogCategory->load('parent')]);
    }

    public function edit(BlogCategory $blogCategory): View
    {
        return view('blog.categories-edit', [
            'category' => $blogCategory,
            'categories' => BlogCategory::query()->with('childrenRecursive')->whereNull('parent_id')->orderBy('name')->get(),
            'excludedCategoryIds' => $this->categoryAndDescendantIds($blogCategory),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $category = BlogCategory::create($this->data($request));
        $this->image($request, $category);

        return to_route('blog.categories')->with('status', 'Blog category created successfully.');
    }

    public function update(Request $request, BlogCategory $blogCategory): RedirectResponse
    {
        $blogCategory->update($this->data($request, $blogCategory));
        $this->image($request, $blogCategory);

        return to_route('blog.categories')->with('status', 'Blog category updated successfully.');
    }

    public function destroy(BlogCategory $blogCategory): RedirectResponse
    {
        $this->mediaLibrary->delete($blogCategory->image_path);
        $blogCategory->delete();

        return to_route('blog.categories')->with('status', 'Blog category deleted successfully.');
    }

    /** @return array<string, mixed> */
    private function data(Request $request, ?BlogCategory $category = null): array
    {
        $id = $category?->id;
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:blog_categories,name'.($id ? ','.$id : '')],
            'slug' => ['nullable', 'string', 'max:100', 'alpha_dash', 'unique:blog_categories,slug'.($id ? ','.$id : '')],
            'parent_id' => ['nullable', 'integer', 'different:'.$id, 'exists:blog_categories,id'],
            'description' => ['nullable', 'string'],
            'extra_description' => ['nullable', 'string'],
            'navigation_image' => ['nullable', 'image', 'max:2048'],
        ]);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        if ($category && ! empty($data['parent_id']) && in_array((int) $data['parent_id'], $this->categoryAndDescendantIds($category), true)) {
            throw ValidationException::withMessages([
                'parent_id' => 'A blog category cannot use itself or one of its subcategories as its parent.',
            ]);
        }

        if (BlogCategory::query()->where('slug', $data['slug'])->when($category, fn ($query) => $query->whereKeyNot($category->id))->exists()) {
            throw ValidationException::withMessages([
                $request->filled('slug') ? 'slug' : 'name' => 'This blog category slug is already in use.',
            ]);
        }

        return $data;
    }

    /** @return list<int> */
    private function categoryAndDescendantIds(BlogCategory $category): array
    {
        $categoryIds = [$category->id];
        $parentIds = [$category->id];

        while ($parentIds !== []) {
            $parentIds = BlogCategory::query()->whereIn('parent_id', $parentIds)->pluck('id')->all();
            $categoryIds = [...$categoryIds, ...$parentIds];
        }

        return $categoryIds;
    }

    private function image(Request $request, BlogCategory $category): void
    {
        if (! $request->hasFile('navigation_image')) {
            return;
        }

        $this->mediaLibrary->delete($category->image_path);
        $category->update([
            'image_path' => $this->mediaLibrary->storeImage($request->file('navigation_image'), 'blog-categories', $category->name),
        ]);
    }
}
