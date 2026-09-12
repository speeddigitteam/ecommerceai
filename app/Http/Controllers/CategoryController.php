<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Services\MediaLibrary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private readonly MediaLibrary $mediaLibrary) {}

    public function index(): View
    {
        return view('categories.index', ['categories' => Category::query()->with('childrenRecursive')->whereNull('parent_id')->orderBy('name')->get()]);
    }

    public function show(Category $category): RedirectResponse
    {
        return redirect()->route('catalog.show', $category->slug, 301);
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', [
            'category' => $category,
            'categories' => Category::query()->with('childrenRecursive')->whereNull('parent_id')->orderBy('name')->get(),
            'excludedCategoryIds' => $this->categoryAndDescendantIds($category),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $category = Category::create($this->data($request));
        $this->images($request, $category);

        if ($request->expectsJson()) {
            return response()->json([
                'category' => $category->only(['id', 'name', 'slug', 'parent_id']),
                'message' => 'Category created successfully.',
            ], 201);
        }

        return to_route('categories.index')->with('status', 'Category created successfully.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($this->data($request, $category));
        $this->images($request, $category);

        return to_route('categories.index')->with('status', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->mediaLibrary->delete([$category->thumbnail_path, $category->page_title_image_path, $category->navigation_image_path, $category->header_menu_image_path, $category->slider_image_path]);
        $category->delete();

        return to_route('categories.index')->with('status', 'Category deleted successfully.');
    }

    /** @return array<string, mixed> */
    private function data(Request $request, ?Category $category = null): array
    {
        $id = $category?->id;
        $data = $request->validate(
            ['name' => ['required', 'string', 'max:100', 'unique:categories,name'.($id ? ','.$id : '')], 'slug' => ['nullable', 'string', 'max:100', 'alpha_dash', 'unique:categories,slug'.($id ? ','.$id : '')], 'parent_id' => ['nullable', 'integer', 'different:'.$id, 'exists:categories,id'], 'description' => ['nullable', 'string'], 'display_type' => ['nullable', 'in:default'], 'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], 'page_title_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], 'navigation_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], 'header_menu_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], 'extra_description' => ['nullable', 'string'], 'slider_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048']],
            [
                '*.image' => 'The selected file must be a valid image.',
                '*.mimes' => 'The image must be a JPG, PNG or WebP file.',
                '*.max' => 'The image could not be uploaded because it is larger than 2 MB.',
                'navigation_image.uploaded' => 'The category image upload did not complete. Check the server upload limit and try again.',
            ],
        );
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $parentId = $data['parent_id'] ?? null;

        if ($category && $parentId && in_array((int) $parentId, $this->categoryAndDescendantIds($category), true)) {
            throw ValidationException::withMessages([
                'parent_id' => 'A category cannot use itself or one of its subcategories as its parent.',
            ]);
        }

        $categoryUsesSlug = Category::query()
            ->where('slug', $data['slug'])
            ->when($category, fn ($query) => $query->whereKeyNot($category->id))
            ->exists();

        if ($this->slugIsReserved($data['slug']) || $categoryUsesSlug || Brand::query()->where('slug', $data['slug'])->exists()) {
            throw ValidationException::withMessages([
                $request->filled('slug') ? 'slug' : 'name' => 'This URL slug is already in use or reserved.',
            ]);
        }

        return $data;
    }

    /** @return list<int> */
    private function categoryAndDescendantIds(Category $category): array
    {
        $categoryIds = [$category->id];
        $parentIds = [$category->id];

        while ($parentIds !== []) {
            $parentIds = Category::query()->whereIn('parent_id', $parentIds)->pluck('id')->all();
            $categoryIds = [...$categoryIds, ...$parentIds];
        }

        return $categoryIds;
    }

    private function slugIsReserved(string $slug): bool
    {
        return in_array($slug, ['login', 'adminlogin', 'logout', 'register', 'dashboard', 'profile', 'users', 'categories', 'brands', 'units', 'settings', 'password', 'forgot-password', 'reset-password', 'verify-email', 'confirm-password'], true);
    }

    private function images(Request $request, Category $category): void
    {
        foreach (['thumbnail' => 'thumbnail_path', 'page_title_image' => 'page_title_image_path', 'navigation_image' => 'navigation_image_path', 'header_menu_image' => 'header_menu_image_path', 'slider_image' => 'slider_image_path'] as $input => $attribute) {
            if ($request->hasFile($input)) {
                if ($category->{$attribute}) {
                    $this->mediaLibrary->delete($category->{$attribute});
                }
                $category->{$attribute} = $this->mediaLibrary->storeImage($request->file($input), 'categories', $category->name);
            }
        }

        $category->save();
    }
}
