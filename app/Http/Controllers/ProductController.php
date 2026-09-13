<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Services\MediaLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly MediaLibrary $mediaLibrary) {}

    public function index(): View
    {
        return view('products.index', ['products' => Product::query()->with(['categories', 'brand'])->latest()->paginate(15)]);
    }

    public function create(): View
    {
        return view('products.form', [...$this->options(), 'product' => new Product]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = Product::query()->create($this->data($request));
        $product->categories()->sync($request->validated('category_ids', []));
        $this->storeMedia($request, $product);
        $this->syncVariants($request, $product);
        $this->syncWholesaleTiers($request, $product);

        return to_route('products.index')->with('status', 'Product created successfully.');
    }

    public function show(Product $product): View
    {
        return view('products.show', ['product' => $product->load(['categories', 'brand', 'unit'])]);
    }

    public function edit(Product $product): View
    {
        return view('products.form', [...$this->options(), 'product' => $product->load(['variants', 'wholesalePriceTiers'])]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($this->data($request, $product));
        $product->categories()->sync($request->validated('category_ids', []));
        $this->storeMedia($request, $product);
        $this->syncVariants($request, $product);
        $this->syncWholesaleTiers($request, $product);

        return to_route('products.index')->with('status', 'Product updated successfully.');
    }

    public function duplicate(Product $product): RedirectResponse
    {
        $duplicate = DB::transaction(function () use ($product): Product {
            $product->load(['categories', 'variants', 'wholesalePriceTiers']);

            $duplicate = $product->replicate();
            $duplicate->title = Str::limit($product->title.' - Copy', 255, '');
            $duplicate->slug = $this->uniqueCopySlug($product->slug);
            $duplicate->sku = $this->uniqueCopySku($product->sku, 'products');
            $duplicate->status = 'draft';
            $duplicate->published_at = null;
            $duplicate->save();
            $duplicate->categories()->sync($product->categories->modelKeys());

            $variantIds = [];
            foreach ($product->variants as $variant) {
                $variantCopy = $variant->replicate();
                $variantCopy->product_id = $duplicate->id;
                $variantCopy->sku = $this->uniqueCopySku($variant->sku, 'product_variants');
                $variantCopy->save();
                $variantIds[$variant->id] = $variantCopy->id;
            }

            foreach ($product->wholesalePriceTiers as $tier) {
                $tierCopy = $tier->replicate();
                $tierCopy->product_id = $duplicate->id;
                $tierCopy->product_variant_id = $tier->product_variant_id
                    ? ($variantIds[$tier->product_variant_id] ?? null)
                    : null;
                $tierCopy->save();
            }

            return $duplicate;
        });

        return to_route('products.edit', $duplicate)
            ->with('status', 'Product duplicated as a draft. Review it before publishing.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $mediaPaths = collect([$product->featured_image_path, ...($product->gallery_paths ?? []), ...$product->variants()->pluck('image_path')->filter()->all()])
            ->filter(fn (?string $path): bool => filled($path) && ! $this->pathIsUsedByAnotherProduct($path, $product));
        $this->mediaLibrary->delete($mediaPaths->all());
        if ($product->video_path && ! $this->pathIsUsedByAnotherProduct($product->video_path, $product)) {
            Storage::disk('public')->delete($product->video_path);
        }
        if ($product->digital_file_path && ! $this->pathIsUsedByAnotherProduct($product->digital_file_path, $product)) {
            Storage::disk('local')->delete($product->digital_file_path);
        }
        $product->delete();

        return to_route('products.index')->with('status', 'Product deleted successfully.');
    }

    /** @return array<string, mixed> */
    private function data(StoreProductRequest $request, ?Product $product = null): array
    {
        $data = $request->safe()->except(['featured_image', 'remove_featured_image', 'gallery', 'remove_gallery_paths', 'video', 'category_ids', 'variants', 'wholesale_tiers', 'digital_file', 'remove_digital_file']);
        $data['slug'] = ($data['slug'] ?? null) ?: Str::slug($data['title']);
        $data['tags'] = array_values(array_filter(array_map('trim', explode(',', $data['tags'] ?? ''))));
        $data['specifications'] = collect($data['specifications'] ?? [])->map(fn (array $section): array => [
            'title' => trim($section['title']),
            'items' => collect($section['items'])->map(fn (array $item): array => [
                'title' => trim($item['title']),
                'value' => trim($item['value']),
            ])->values()->all(),
        ])->values()->all();
        $data['questions'] = collect($data['questions'] ?? [])->map(fn (array $question): array => [
            'question' => trim($question['question']),
            'answer' => trim($question['answer']),
        ])->values()->all();
        $data['published_at'] = $data['status'] === 'published' ? (($data['published_at'] ?? null) ?: $product?->published_at ?: now()) : null;

        if ($data['type'] === 'digital') {
            $data['stock_quantity'] = Product::UNLIMITED_STOCK;
        }

        $variants = collect($request->validated('variants', []));
        if ($variants->isNotEmpty()) {
            $pricedVariants = $variants->filter(fn (array $variant): bool => ($variant['price'] ?? null) !== null && $variant['price'] !== '');
            $saleVariants = $variants->filter(fn (array $variant): bool => ($variant['sale_price'] ?? null) !== null && $variant['sale_price'] !== '');
            $data['price'] = $pricedVariants->isNotEmpty() ? $pricedVariants->min(fn (array $variant): float => (float) $variant['price']) : ($data['price'] ?? null);
            $data['sale_price'] = $saleVariants->isNotEmpty() ? $saleVariants->min(fn (array $variant): float => (float) $variant['sale_price']) : null;
            $data['stock_quantity'] = (int) $variants->sum(fn (array $variant): int => (int) $variant['stock_quantity']);
        }

        return $data;
    }

    private function syncWholesaleTiers(StoreProductRequest $request, Product $product): void
    {
        $product->wholesalePriceTiers()->whereNull('product_variant_id')->delete();
        foreach (collect($request->validated('wholesale_tiers', []))->filter(fn (array $tier): bool => filled($tier['minimum_quantity'] ?? null) && filled($tier['unit_price'] ?? null)) as $tier) {
            $product->wholesalePriceTiers()->create([
                'minimum_quantity' => $tier['minimum_quantity'],
                'unit_price' => $tier['unit_price'],
            ]);
        }
    }

    private function syncVariants(StoreProductRequest $request, Product $product): void
    {
        $submitted = collect($request->validated('variants', []));
        $existingVariants = $product->variants()->get()->keyBy('id');
        $keptIds = [];

        foreach ($submitted as $index => $variantData) {
            $variant = isset($variantData['id']) ? $existingVariants->get((int) $variantData['id']) : null;
            $attributes = [
                'sku' => $variantData['sku'] ?? null,
                'price' => $variantData['price'] ?? null,
                'sale_price' => $variantData['sale_price'] ?? null,
                'stock_quantity' => (int) $variantData['stock_quantity'],
                'options' => collect($variantData['options'])->mapWithKeys(fn (array $option): array => [trim($option['name']) => trim($option['value'])])->all(),
            ];

            $imageInput = "variants.{$index}.image";
            if ($request->hasFile($imageInput)) {
                if ($variant?->image_path && ! $this->pathIsUsedByAnotherProduct($variant->image_path, $product)) {
                    $this->mediaLibrary->delete($variant->image_path);
                }
                $attributes['image_path'] = $this->mediaLibrary->storeImage($request->file($imageInput), 'products/variants', $product->title);
            } elseif (($variantData['remove_image'] ?? false) && $variant?->image_path) {
                if (! $this->pathIsUsedByAnotherProduct($variant->image_path, $product)) {
                    $this->mediaLibrary->delete($variant->image_path);
                }
                $attributes['image_path'] = null;
            }

            $variant = $variant ? tap($variant)->update($attributes) : $product->variants()->create($attributes);
            $keptIds[] = $variant->id;
        }

        $removedVariants = $existingVariants->except($keptIds);
        if ($removedVariants->isNotEmpty()) {
            $this->mediaLibrary->delete($removedVariants->pluck('image_path')->filter(
                fn (?string $path): bool => filled($path) && ! $this->pathIsUsedByAnotherProduct($path, $product)
            )->all());
            ProductVariant::query()->whereIn('id', $removedVariants->keys())->delete();
        }

        if ($submitted->isNotEmpty()) {
            $product->syncAggregatesFromVariants();
        }
    }

    private function storeMedia(StoreProductRequest $request, Product $product): void
    {
        foreach (['featured_image' => 'featured_image_path', 'video' => 'video_path'] as $input => $attribute) {
            if ($request->hasFile($input)) {
                if ($product->{$attribute}) {
                    if (! $this->pathIsUsedByAnotherProduct($product->{$attribute}, $product)) {
                        $input === 'video' ? Storage::disk('public')->delete($product->{$attribute}) : $this->mediaLibrary->delete($product->{$attribute});
                    }
                }

                $product->{$attribute} = $input === 'video' ? $request->file($input)->store('products', 'public') : $this->mediaLibrary->storeImage($request->file($input), 'products', $product->title);
            } elseif ($input === 'featured_image' && $request->boolean('remove_featured_image') && $product->featured_image_path) {
                if (! $this->pathIsUsedByAnotherProduct($product->featured_image_path, $product)) {
                    $this->mediaLibrary->delete($product->featured_image_path);
                }

                $product->featured_image_path = null;
            }
        }

        $galleryPathsToRemove = collect($request->validated('remove_gallery_paths', []))
            ->intersect($product->gallery_paths ?? [])
            ->values();
        if ($galleryPathsToRemove->isNotEmpty()) {
            $this->mediaLibrary->delete($galleryPathsToRemove->filter(
                fn (string $path): bool => ! $this->pathIsUsedByAnotherProduct($path, $product)
            )->all());
            $product->gallery_paths = collect($product->gallery_paths ?? [])
                ->reject(fn (string $path): bool => $galleryPathsToRemove->contains($path))
                ->values()
                ->all();
        }

        if ($request->hasFile('gallery')) {
            $product->gallery_paths = [...($product->gallery_paths ?? []), ...array_map(fn (UploadedFile $file): string => $this->mediaLibrary->storeImage($file, 'products/gallery', $product->title), $request->file('gallery'))];
        }

        if ($request->hasFile('digital_file')) {
            if ($product->digital_file_path && ! $this->pathIsUsedByAnotherProduct($product->digital_file_path, $product)) {
                Storage::disk('local')->delete($product->digital_file_path);
            }
            $file = $request->file('digital_file');
            $product->digital_file_path = $file->store('digital-products', 'local');
            $product->digital_file_name = $file->getClientOriginalName();
        } elseif ($request->boolean('remove_digital_file') && $product->digital_file_path) {
            if (! $this->pathIsUsedByAnotherProduct($product->digital_file_path, $product)) {
                Storage::disk('local')->delete($product->digital_file_path);
            }
            $product->digital_file_path = null;
            $product->digital_file_name = null;
        }

        $product->save();
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        return ['categories' => Category::query()->whereNull('parent_id')->with('childrenRecursive')->orderBy('name')->get(), 'brands' => Brand::query()->orderBy('name')->get(), 'units' => Unit::query()->orderBy('name')->get()];
    }

    private function uniqueCopySlug(string $slug): string
    {
        $base = Str::limit(Str::slug($slug.'-copy'), 245, '');
        $candidate = $base;
        $suffix = 2;

        while (Product::query()->where('slug', $candidate)->exists()) {
            $candidate = Str::limit($base, 244 - strlen((string) $suffix), '').'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function uniqueCopySku(?string $sku, string $table): ?string
    {
        if (blank($sku)) {
            return null;
        }

        $base = Str::limit($sku.'-COPY', 90, '');
        $candidate = $base;
        $suffix = 2;

        while (DB::table($table)->where('sku', $candidate)->exists()) {
            $candidate = Str::limit($base, 98 - strlen((string) $suffix), '').'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function pathIsUsedByAnotherProduct(string $path, Product $product): bool
    {
        $usedByProduct = Product::query()
            ->whereKeyNot($product->id)
            ->get(['featured_image_path', 'gallery_paths', 'video_path', 'digital_file_path'])
            ->contains(fn (Product $otherProduct): bool => in_array($path, [
                $otherProduct->featured_image_path,
                ...($otherProduct->gallery_paths ?? []),
                $otherProduct->video_path,
                $otherProduct->digital_file_path,
            ], true));

        return $usedByProduct || ProductVariant::query()
            ->where('product_id', '!=', $product->id)
            ->where('image_path', $path)
            ->exists();
    }
}
