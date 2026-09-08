<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\WebsiteSetting;
use App\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function index(Request $request): View
    {
        return $this->storefront($request);
    }

    public function bestSelling(): View
    {
        $products = Product::query()
            ->with(['brand', 'categories'])
            ->withSum([
                'orderItems as sold_quantity' => fn (Builder $query) => $query
                    ->whereHas('order', fn (Builder $orderQuery) => $orderQuery->where('status', OrderStatus::Completed->value)),
            ], 'quantity')
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNotNull('price')
            ->orderByDesc('sold_quantity')
            ->latest()
            ->paginate(20);

        return view('storefront.best-selling', ['products' => $products]);
    }

    public function flashSale(): View
    {
        $flashSale = array_merge(
            WebsiteSetting::defaultFlashSaleSettings(),
            WebsiteSetting::query()->first()?->flash_sale_settings ?? [],
        );

        $products = Product::query()
            ->with(['brand', 'categories'])
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNotNull('price')
            ->where('price', '>', 0)
            ->whereNotNull('sale_price')
            ->whereColumn('sale_price', '<', 'price')
            ->orderByRaw('(price - sale_price) / price DESC')
            ->latest('published_at')
            ->when(
                ! $flashSale['include_all_sale_products'],
                fn (Builder $query) => $query->whereKey($flashSale['product_ids']),
            )
            ->paginate(20);

        return view('storefront.product-listing', [
            'products' => $products,
            'pageTitle' => $flashSale['title'],
            'eyebrow' => $flashSale['eyebrow'],
            'description' => $flashSale['description'],
            'canonical' => route('storefront.flash-sale'),
            'badge' => null,
        ]);
    }

    public function newArrivals(): View
    {
        $products = Product::query()
            ->with(['brand', 'categories'])
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNotNull('price')
            ->whereBetween('published_at', [now()->subDays(30), now()])
            ->latest('published_at')
            ->paginate(20);

        return view('storefront.product-listing', [
            'products' => $products,
            'pageTitle' => 'New Arrivals',
            'eyebrow' => 'Just landed',
            'description' => 'Discover products added to our collection during the last 30 days.',
            'canonical' => route('storefront.new-arrivals'),
            'badge' => 'New',
        ]);
    }

    public function latestProducts(): View
    {
        $products = Product::query()
            ->with(['brand', 'categories'])
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNotNull('price')
            ->latest('published_at')
            ->paginate(20);

        return view('storefront.product-listing', [
            'products' => $products,
            'pageTitle' => 'Latest Products',
            'eyebrow' => 'Our collection',
            'description' => 'Browse all of our latest published products, with the newest additions shown first.',
            'canonical' => route('storefront.latest-products'),
            'badge' => null,
        ]);
    }

    public function category(Request $request, Category $category): View
    {
        $category->load(['childrenRecursive', 'parentRecursive']);

        return $this->storefront($request, $category);
    }

    public function brand(Request $request, Brand $brand): View
    {
        return $this->storefront($request, activeBrand: $brand);
    }

    private function storefront(Request $request, ?Category $activeCategory = null, ?Brand $activeBrand = null): View
    {
        $heroSettings = WebsiteSetting::query()->first();
        $categoryIds = $activeCategory ? $this->categoryTreeIds($activeCategory) : [];
        $catalogProductQuery = Product::query()
            ->where('status', 'published')->where('visibility', 'public')->whereNotNull('price')
            ->when($activeCategory, fn (Builder $query) => $query->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->whereIn('categories.id', $categoryIds)))
            ->when($activeBrand, fn (Builder $query) => $query->where('brand_id', $activeBrand->id));
        $catalogProductCount = (clone $catalogProductQuery)->count();
        $priceBounds = (clone $catalogProductQuery)
            ->selectRaw('MIN(price) as minimum_price')
            ->selectRaw('MAX(price) as maximum_price')
            ->first();
        $minimumProductPrice = (int) floor((float) ($priceBounds?->minimum_price ?? 0));
        $maximumProductPrice = (int) ceil((float) ($priceBounds?->maximum_price ?? 0));

        $productQuery = (clone $catalogProductQuery)->with(['brand', 'categories'])
            ->when($request->string('search')->trim()->isNotEmpty(), function (Builder $query) use ($request): void {
                $search = $request->string('search')->trim()->toString();
                $query->where(fn (Builder $productQuery) => $productQuery->where('title', 'like', "%{$search}%")->orWhere('short_description', 'like', "%{$search}%"));
            })
            ->when($request->boolean('sale'), fn (Builder $query) => $query->whereNotNull('sale_price')->whereColumn('sale_price', '<', 'price'))
            ->when($request->filled('min_price') && is_numeric($request->input('min_price')), fn (Builder $query) => $this->filterByCurrentPrice($query, '>=', (float) $request->input('min_price')))
            ->when($request->filled('max_price') && is_numeric($request->input('max_price')), fn (Builder $query) => $this->filterByCurrentPrice($query, '<=', (float) $request->input('max_price')))
            ->when($request->array('availability') !== [], function (Builder $query) use ($request): void {
                $availability = array_intersect($request->array('availability'), ['in_stock', 'pre_order', 'upcoming']);
                $query->where(function (Builder $availabilityQuery) use ($availability): void {
                    foreach ($availability as $option) {
                        $availabilityQuery->orWhere(function (Builder $optionQuery) use ($option): void {
                            match ($option) {
                                'in_stock' => $optionQuery->where('stock_quantity', '>', 0),
                                'pre_order' => $optionQuery->where('stock_quantity', 0)->where(fn (Builder $dateQuery) => $dateQuery->whereNull('published_at')->orWhere('published_at', '<=', now())),
                                'upcoming' => $optionQuery->where('published_at', '>', now()),
                            };
                        });
                    }
                });
            });

        match ($request->string('sort')->toString()) {
            'price_low' => $productQuery->orderByRaw('CASE WHEN sale_price IS NULL THEN price ELSE sale_price END ASC'),
            'price_high' => $productQuery->orderByRaw('CASE WHEN sale_price IS NULL THEN price ELSE sale_price END DESC'),
            'name_asc' => $productQuery->orderBy('title'),
            'name_desc' => $productQuery->orderByDesc('title'),
            default => $productQuery->latest('published_at'),
        };

        $requestedPerPage = $request->integer('per_page', $activeCategory || $activeBrand ? 20 : 12);
        $perPage = in_array($requestedPerPage, [12, 20, 40, 60], true) ? $requestedPerPage : 20;
        $products = $productQuery->paginate($perPage)->withQueryString();

        if ($request->boolean('infinite') && $request->routeIs('storefront.index')) {
            return view('storefront.partials.product-page', ['products' => $products]);
        }

        $heroProduct = $heroSettings?->hero_product_id
            ? Product::query()->with(['brand', 'categories'])->whereKey($heroSettings->hero_product_id)->where('status', 'published')->where('visibility', 'public')->whereNotNull('price')->first()
            : null;
        $catalogLinks = $this->catalogLinks($activeCategory, $activeBrand, $categoryIds);

        $bestSellingProducts = collect();
        $newArrivalProducts = collect();
        $latestReviews = collect();
        $latestBlogPosts = collect();

        if (! $activeCategory && ! $activeBrand) {
            $bestSellingProducts = Product::query()
                ->with(['brand', 'categories'])
                ->withSum([
                    'orderItems as sold_quantity' => fn (Builder $query) => $query
                        ->whereHas('order', fn (Builder $orderQuery) => $orderQuery->where('status', OrderStatus::Completed->value)),
                ], 'quantity')
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->whereNotNull('price')
                ->orderByDesc('sold_quantity')
                ->latest()
                ->limit(5)
                ->get();

            $newArrivalProducts = Product::query()
                ->with(['brand', 'categories'])
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->whereNotNull('price')
                ->latest('published_at')
                ->limit(5)
                ->get();

            $latestReviews = ProductReview::query()
                ->with(['user', 'product'])
                ->whereNotNull('order_id')
                ->whereHas('product', fn (Builder $query) => $query
                    ->where('status', 'published')
                    ->where('visibility', 'public')
                    ->whereNotNull('price'))
                ->latest()
                ->limit(3)
                ->get();

            $latestBlogPosts = BlogPost::query()
                ->with('categories')
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->latest('published_at')
                ->limit(3)
                ->get();
        }

        return view('storefront.index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->get(),
            'heroSettings' => $heroSettings,
            'heroProduct' => $heroProduct ?? $products->first(),
            'activeCategory' => $activeCategory,
            'activeBrand' => $activeBrand,
            'catalogLinks' => $catalogLinks,
            'bestSellingProducts' => $bestSellingProducts,
            'newArrivalProducts' => $newArrivalProducts,
            'latestReviews' => $latestReviews,
            'latestBlogPosts' => $latestBlogPosts,
            'minimumProductPrice' => $minimumProductPrice,
            'maximumProductPrice' => $maximumProductPrice,
            'catalogProductCount' => $catalogProductCount,
        ]);
    }

    /** @return list<array{name: string, url: string}> */
    private function catalogLinks(?Category $activeCategory, ?Brand $activeBrand, array $categoryIds): array
    {
        if ($activeCategory?->childrenRecursive->isNotEmpty()) {
            return $activeCategory->childrenRecursive->map(fn (Category $category): array => [
                'name' => $category->name,
                'url' => route('catalog.show', $category->slug),
            ])->values()->all();
        }

        if ($activeCategory) {
            return Brand::query()->whereNotNull('slug')->whereHas('products', fn (Builder $query) => $query->where('status', 'published')->where('visibility', 'public')->whereNotNull('price')->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->whereIn('categories.id', $categoryIds)))->orderBy('name')->get()->map(fn (Brand $brand): array => [
                'name' => $brand->name,
                'url' => route('catalog.show', $brand->slug),
            ])->all();
        }

        if ($activeBrand) {
            return Category::query()->whereHas('products', fn (Builder $query) => $query->where('brand_id', $activeBrand->id)->where('status', 'published')->where('visibility', 'public')->whereNotNull('price'))->orderBy('name')->get()->map(fn (Category $category): array => [
                'name' => $category->name,
                'url' => route('catalog.show', $category->slug),
            ])->all();
        }

        return [];
    }

    /** @return list<int> */
    private function categoryTreeIds(Category $category): array
    {
        $ids = [$category->id];

        foreach ($category->childrenRecursive as $child) {
            array_push($ids, ...$this->categoryTreeIds($child));
        }

        return $ids;
    }

    private function filterByCurrentPrice(Builder $query, string $operator, float $price): void
    {
        $query->where(function (Builder $priceQuery) use ($operator, $price): void {
            $priceQuery->where(fn (Builder $regularPriceQuery) => $regularPriceQuery->whereNull('sale_price')->where('price', $operator, $price))
                ->orWhere(fn (Builder $salePriceQuery) => $salePriceQuery->whereNotNull('sale_price')->where('sale_price', $operator, $price));
        });
    }

    public function show(Product $product): View
    {
        abort_unless($product->status === 'published' && $product->visibility === 'public' && $product->price !== null, 404);

        $product->load(['brand', 'unit', 'categories.parentRecursive'])
            ->loadCount('reviews')
            ->loadAvg('reviews', 'rating');
        $canReview = auth()->check()
            && ! auth()->user()->isAdmin()
            && auth()->user()->completedOrderForProduct($product) !== null;

        return view('storefront.show', [
            'product' => $product,
            'reviews' => $product->reviews()->with('user')->latest()->get(),
            'canReview' => $canReview,
            'relatedProducts' => Product::query()->whereKeyNot($product->id)->where('status', 'published')->where('visibility', 'public')->whereNotNull('price')->latest('published_at')->limit(5)->get(),
        ]);
    }
}
