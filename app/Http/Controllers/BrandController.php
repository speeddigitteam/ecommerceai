<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        return view('brands.index', ['brands' => Brand::query()->orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:brands,name'],
            'slug' => ['nullable', 'string', 'max:100', 'alpha_dash', 'unique:brands,slug'],
            'description' => ['nullable', 'string'],
            'extra_description' => ['nullable', 'string'],
        ]);
        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        $this->ensureSlugIsAvailable($slug);
        $brand = Brand::create([...$validated, 'slug' => $slug]);

        if ($request->expectsJson()) {
            return response()->json([
                'brand' => $brand->only(['id', 'name', 'slug']),
                'message' => 'Brand created successfully.',
            ], 201);
        }

        return to_route('brands.index')->with('status', 'Brand created successfully.');
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:brands,name,'.$brand->id],
            'slug' => ['nullable', 'string', 'max:100', 'alpha_dash', 'unique:brands,slug,'.$brand->id],
            'description' => ['nullable', 'string'],
            'extra_description' => ['nullable', 'string'],
        ]);
        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        $this->ensureSlugIsAvailable($slug, $brand);
        $brand->update([...$validated, 'slug' => $slug]);

        return to_route('brands.index')->with('status', 'Brand updated successfully.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $brand->delete();

        return to_route('brands.index')->with('status', 'Brand deleted successfully.');
    }

    private function ensureSlugIsAvailable(string $slug, ?Brand $brand = null): void
    {
        $brandQuery = Brand::query()->where('slug', $slug);

        if ($brand) {
            $brandQuery->whereKeyNot($brand);
        }

        $brandUsesSlug = $brandQuery->exists();
        $reserved = in_array($slug, ['login', 'adminlogin', 'logout', 'register', 'dashboard', 'profile', 'users', 'categories', 'brands', 'units', 'settings', 'password', 'forgot-password', 'reset-password', 'verify-email', 'confirm-password'], true);

        if ($reserved || $brandUsesSlug || Category::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages([request()->filled('slug') ? 'slug' : 'name' => 'This URL slug is already in use or reserved.']);
        }
    }
}
