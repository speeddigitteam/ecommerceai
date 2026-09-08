<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_front_page_uses_website_site_name_and_seo_title(): void
    {
        WebsiteSetting::factory()->create([
            'site_name' => 'Acme Shop',
            'seo_title' => 'Best Products Online',
        ]);

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('<title>Best Products Online | Acme Shop</title>', false)
            ->assertSee('Acme Shop');
    }

    public function test_front_page_shows_flash_sale_banner(): void
    {
        Category::factory()->create();

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('Flash Sale')
            ->assertSee('Limited time')
            ->assertSee('aria-label="Flash sale countdown"', false)
            ->assertSee('href="'.route('storefront.flash-sale').'"', false)
            ->assertSeeInOrder(['id="shop-categories"', 'aria-label="Flash sale"'], false);
    }

    public function test_flash_sale_view_all_opens_a_discount_ordered_sale_products_page(): void
    {
        Product::factory()->create([
            'title' => 'Biggest Flash Discount',
            'price' => 1000,
            'sale_price' => 500,
        ]);
        Product::factory()->create([
            'title' => 'Smaller Flash Discount',
            'price' => 1000,
            'sale_price' => 900,
        ]);
        Product::factory()->create([
            'title' => 'Not Actually Discounted',
            'price' => 1000,
            'sale_price' => 1000,
        ]);
        Product::factory()->create([
            'title' => 'Hidden Flash Discount',
            'price' => 1000,
            'sale_price' => 400,
            'status' => 'draft',
        ]);

        $this->get(route('storefront.flash-sale'))
            ->assertOk()
            ->assertSee('<h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950 dark:text-white sm:text-4xl">Flash Sale</h1>', false)
            ->assertSeeInOrder(['Biggest Flash Discount', 'Smaller Flash Discount'])
            ->assertDontSee('Not Actually Discounted')
            ->assertDontSee('Hidden Flash Discount');
    }

    public function test_storefront_header_uses_dynamic_identity_navigation_and_core_actions(): void
    {
        WebsiteSetting::factory()->create(['site_name' => 'Dynamic Shop']);
        $category = Category::factory()->create(['name' => 'Dynamic Category', 'slug' => 'dynamic-category']);

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('data-storefront-header', false)
            ->assertSee('bg-white text-slate-900', false)
            ->assertSee('dark:bg-[#071d32] dark:text-white', false)
            ->assertSee('Dynamic Shop')
            ->assertSee('Dynamic Category')
            ->assertSee('Search products, brands and categories')
            ->assertSee('shadow-[0_2px_8px_rgba(15,23,42,.08)]', false)
            ->assertSee('href="'.route('storefront.orders.track').'"', false)
            ->assertSee('href="'.route('login').'"', false)
            ->assertSee('href="'.route('cart.index').'"', false)
            ->assertSee('id="mobile-store-menu"', false);
    }

    public function test_front_page_shows_best_selling_design_before_products_have_sales(): void
    {
        Product::factory()->create(['title' => 'New Product Without Sales']);

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('id="best-selling-products"', false)
            ->assertSee('Best Selling Products')
            ->assertSee('New Product Without Sales')
            ->assertDontSee('0 sold');
    }

    public function test_best_selling_view_all_opens_a_paginated_sales_ordered_product_page(): void
    {
        $mostSold = Product::factory()->create(['title' => 'Most Sold Product']);
        $lessSold = Product::factory()->create(['title' => 'Less Sold Product']);
        $withoutSales = Product::factory()->create(['title' => 'Product Without Sales']);
        Product::factory()->create([
            'title' => 'Hidden Best Seller',
            'status' => 'draft',
        ]);
        $order = Order::factory()->create([
            'order_number' => 'ORD-BEST-001',
            'customer_name' => 'Best Seller Customer',
            'customer_phone' => '01700000000',
            'shipping_address' => 'Dhaka, Bangladesh',
            'subtotal' => 700,
            'shipping_cost' => 50,
            'total' => 750,
            'status' => 'completed',
        ]);
        $order->items()->createMany([
            [
                'product_id' => $mostSold->id,
                'product_title' => $mostSold->title,
                'unit_price' => 100,
                'quantity' => 5,
                'line_total' => 500,
            ],
            [
                'product_id' => $lessSold->id,
                'product_title' => $lessSold->title,
                'unit_price' => 100,
                'quantity' => 2,
                'line_total' => 200,
            ],
        ]);

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('href="'.route('storefront.best-selling').'"', false);

        $this->get(route('storefront.best-selling'))
            ->assertOk()
            ->assertSee('<h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950 dark:text-white sm:text-4xl">Best Selling Products</h1>', false)
            ->assertSeeInOrder(['Most Sold Product', 'Less Sold Product', 'Product Without Sales'])
            ->assertSee('5 sold')
            ->assertSee('2 sold')
            ->assertDontSee('Hidden Best Seller');
    }

    public function test_new_arrivals_and_latest_products_view_all_pages_use_the_correct_product_sets(): void
    {
        $recentProduct = Product::factory()->create([
            'title' => 'Recently Published Product',
            'published_at' => now()->subDay(),
        ]);
        Product::factory()->create([
            'title' => 'Older Published Product',
            'published_at' => now()->subDays(45),
        ]);
        Product::factory()->create([
            'title' => 'Hidden Listing Product',
            'status' => 'draft',
            'published_at' => now(),
        ]);

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('href="'.route('storefront.new-arrivals').'"', false)
            ->assertSee('href="'.route('storefront.latest-products').'"', false);

        $this->get(route('storefront.new-arrivals'))
            ->assertOk()
            ->assertSee('<h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950 dark:text-white sm:text-4xl">New Arrivals</h1>', false)
            ->assertSee($recentProduct->title)
            ->assertDontSee('Older Published Product')
            ->assertDontSee('Hidden Listing Product');

        $this->get(route('storefront.latest-products'))
            ->assertOk()
            ->assertSee('<h1 class="mt-1 text-3xl font-semibold tracking-tight text-slate-950 dark:text-white sm:text-4xl">Latest Products</h1>', false)
            ->assertSeeInOrder(['Recently Published Product', 'Older Published Product'])
            ->assertDontSee('Hidden Listing Product');
    }

    public function test_front_page_has_a_separate_five_product_new_arrivals_section(): void
    {
        foreach (range(1, 6) as $index) {
            Product::factory()->create([
                'title' => "Arrival {$index}",
                'published_at' => now()->subMinutes(6 - $index),
            ]);
        }

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSeeInOrder(['id="new-arrivals"', 'id="latest-products"'], false)
            ->assertSee('New Arrivals')
            ->assertSee('Our collection')
            ->assertSee('Latest products')
            ->assertViewHas('newArrivalProducts', fn ($products): bool => $products->count() === 5
                && $products->first()->title === 'Arrival 6'
                && ! $products->contains('title', 'Arrival 1'));
    }

    public function test_storefront_layout_supports_persistent_dark_mode(): void
    {
        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('x-data="storefrontChrome()"', false)
            ->assertSee("localStorage.getItem('admin-theme')", false)
            ->assertSee('Switch to dark mode')
            ->assertSee('storefront-theme', false);
    }

    public function test_front_page_shows_the_missing_seo_and_conversion_sections_with_live_data(): void
    {
        $bestSeller = Product::factory()->create(['title' => 'Customer Favourite Filter']);
        $order = Order::factory()->create([
            'order_number' => 'ORD-SEO-001',
            'customer_name' => 'SEO Customer',
            'customer_phone' => '01700000000',
            'shipping_address' => 'Dhaka, Bangladesh',
            'subtotal' => 1000,
            'shipping_cost' => 50,
            'total' => 1050,
            'status' => 'completed',
        ]);
        $order->items()->create([
            'product_id' => $bestSeller->id,
            'product_title' => $bestSeller->title,
            'unit_price' => 500,
            'quantity' => 2,
            'line_total' => 1000,
        ]);
        ProductReview::factory()->create([
            'product_id' => $bestSeller->id,
            'order_id' => $order->id,
            'rating' => 5,
            'body' => 'Excellent quality and very reliable delivery service.',
        ]);
        BlogPost::factory()->create([
            'title' => 'Product Buying Guide for Bangladesh',
            'status' => 'published',
            'visibility' => 'public',
            'published_at' => now(),
        ]);

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSeeInOrder([
                'id="best-selling-products"',
                'aria-label="Flash sale"',
                'id="latest-products"',
                'id="why-choose-us"',
                'id="customer-reviews"',
                'id="about-us"',
                'id="frequently-asked-questions"',
                'id="latest-from-blog"',
            ], false)
            ->assertSee('Customer Favourite Filter')
            ->assertSee('2 sold')
            ->assertSee('Excellent quality and very reliable delivery service.')
            ->assertSee('Product Buying Guide for Bangladesh')
            ->assertSee('FAQPage')
            ->assertSee('Why Choose')
            ->assertSee('A simpler way to shop online in Bangladesh');
    }

    public function test_front_page_uses_uncropped_five_column_product_cards_and_infinite_loading(): void
    {
        Product::factory()->create([
            'title' => 'Full Image Product',
            'featured_image_path' => 'products/full-image.jpg',
        ]);

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('aspect-square', false)
            ->assertSee('object-contain', false)
            ->assertDontSee('object-contain p-3', false)
            ->assertDontSee('object-cover transition duration-500', false)
            ->assertSee('max-w-[1400px]', false)
            ->assertSee('lg:grid-cols-5', false)
            ->assertSee('id="storefront-product-grid"', false)
            ->assertSee('data-product-limit="100"', false)
            ->assertSee(route('storefront.shop'))
            ->assertSee('View All Products');
    }

    public function test_discounted_product_card_keeps_sale_badge_and_shows_percentage_beside_price(): void
    {
        Product::factory()->create([
            'title' => 'Discount Badge Product',
            'price' => 1000,
            'sale_price' => 800,
        ]);

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('Sale')
            ->assertSee('Discount Badge Product')
            ->assertSee('data-discount-percentage', false)
            ->assertSee('20% OFF');
    }

    public function test_front_page_infinite_request_returns_only_the_next_product_cards(): void
    {
        Product::factory()->count(13)->create();

        $this->get(route('storefront.index', ['infinite' => 1, 'page' => 2]))
            ->assertOk()
            ->assertSee('data-store-product-card', false)
            ->assertSee('data-infinite-meta', false)
            ->assertDontSee('<!DOCTYPE html>', false);
    }

    public function test_shop_page_keeps_standard_product_pagination(): void
    {
        Product::factory()->count(13)->create();

        $this->get(route('storefront.shop'))
            ->assertOk()
            ->assertSee('Product pagination')
            ->assertDontSee('id="storefront-product-grid"', false);
    }

    public function test_category_page_has_seo_metadata_and_includes_subcategory_products(): void
    {
        WebsiteSetting::factory()->create([
            'site_name' => 'Acme Shop',
            'flash_sale_settings' => array_merge(WebsiteSetting::defaultFlashSaleSettings(), [
                'background_from' => '#120010',
                'background_via' => '#76004f',
                'background_to' => '#e03090',
            ]),
        ]);
        $parentCategory = Category::factory()->create([
            'name' => 'Water Filters',
            'slug' => 'water-filters',
            'description' => 'Shop reliable water filters for your home.',
        ]);
        $childCategory = Category::factory()->create(['parent_id' => $parentCategory->id]);
        $product = Product::factory()->create(['title' => 'Pure Water Filter']);
        $product->categories()->attach($childCategory);

        $this->get(route('catalog.show', $parentCategory->slug))
            ->assertOk()
            ->assertSee('<title>Water Filters</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('catalog.show', $parentCategory->slug).'">', false)
            ->assertSee('Shop reliable water filters for your home.')
            ->assertSee('data-catalog-hero', false)
            ->assertSee('linear-gradient(to right, #120010, #76004f, #e03090)', false)
            ->assertSee('1 product')
            ->assertSee('Cash on Delivery')
            ->assertSee('Nationwide delivery')
            ->assertSee('Pure Water Filter')
            ->assertDontSee('id="shop-categories"', false);
    }

    public function test_subcategory_breadcrumb_shows_the_full_category_hierarchy(): void
    {
        $mainCategory = Category::factory()->create(['name' => 'Home Appliances', 'slug' => 'home-appliances']);
        $subCategory = Category::factory()->create(['name' => 'Kitchen', 'slug' => 'kitchen', 'parent_id' => $mainCategory->id]);
        $nestedCategory = Category::factory()->create(['name' => 'Blenders', 'slug' => 'blenders', 'parent_id' => $subCategory->id]);

        $this->get(route('catalog.show', $nestedCategory->slug))
            ->assertOk()
            ->assertSeeInOrder(['Home', 'Home Appliances', 'Kitchen', 'Blenders'])
            ->assertSee(route('catalog.show', $mainCategory->slug))
            ->assertSee(route('catalog.show', $subCategory->slug))
            ->assertSee('BreadcrumbList');
    }

    public function test_catalog_sidebar_filters_products_by_price_and_availability(): void
    {
        $category = Category::factory()->create(['slug' => 'filters']);
        $matchingProduct = Product::factory()->create(['title' => 'Matching Filter', 'price' => 500, 'sale_price' => null, 'stock_quantity' => 4]);
        $expensiveProduct = Product::factory()->create(['title' => 'Expensive Filter', 'price' => 1500, 'sale_price' => null, 'stock_quantity' => 4]);
        $unavailableProduct = Product::factory()->create(['title' => 'Unavailable Filter', 'price' => 500, 'sale_price' => null, 'stock_quantity' => 0]);
        $category->products()->attach([$matchingProduct->id, $expensiveProduct->id, $unavailableProduct->id]);

        $this->get(route('catalog.show', $category->slug).'?min_price=100&max_price=1000')
            ->assertSee('Matching Filter');

        $this->get(route('catalog.show', $category->slug).'?availability[]=in_stock')
            ->assertSee('Matching Filter');

        $this->get(route('catalog.show', $category->slug).'?min_price=100&max_price=1000&availability[]=in_stock')
            ->assertOk()
            ->assertSee('Price Range')
            ->assertSee('Availability')
            ->assertSee('Matching Filter')
            ->assertDontSee('Expensive Filter')
            ->assertDontSee('Unavailable Filter');
    }

    public function test_catalog_toolbar_changes_sorting_and_products_per_page(): void
    {
        $category = Category::factory()->create(['name' => 'Laptops', 'slug' => 'laptops']);
        $lowPriceProduct = Product::factory()->create(['title' => 'Budget Laptop', 'price' => 400, 'sale_price' => null]);
        $highPriceProduct = Product::factory()->create(['title' => 'Premium Laptop', 'price' => 1400, 'sale_price' => null]);
        $category->products()->attach([$lowPriceProduct->id, $highPriceProduct->id]);

        $this->get(route('catalog.show', $category->slug).'?sort=price_high&per_page=40')
            ->assertOk()
            ->assertSee('Show:')
            ->assertSee('Sort By:')
            ->assertSeeInOrder(['Premium Laptop', 'Budget Laptop'])
            ->assertSee('<option value="40" selected>40</option>', false)
            ->assertSee('<option value="price_high" selected>Price: High to Low</option>', false);
    }

    public function test_catalog_page_shows_pagination_summary_and_bottom_description(): void
    {
        $category = Category::factory()->create([
            'name' => 'Gaming Laptops',
            'slug' => 'gaming-laptops',
            'extra_description' => '<p>Compare gaming laptops and choose the best configuration.</p>',
        ]);
        $products = Product::factory()->count(13)->create();
        $category->products()->attach($products->modelKeys());

        $this->get(route('catalog.show', $category->slug).'?per_page=12')
            ->assertOk()
            ->assertSee('Product pagination')
            ->assertSee('Showing 1 to 12 of 13 (2 Pages)')
            ->assertDontSee('About Gaming Laptops')
            ->assertSee('Compare gaming laptops and choose the best configuration.');
    }

    public function test_brand_page_has_clean_url_seo_metadata_and_brand_products(): void
    {
        WebsiteSetting::factory()->create(['site_name' => 'Acme Shop']);
        $brand = Brand::factory()->create([
            'name' => 'Pure Life',
            'slug' => 'pure-life',
            'description' => '<p>Pure Life top brand description.</p>',
            'extra_description' => '<p>Pure Life bottom brand description.</p>',
        ]);
        $otherBrand = Brand::factory()->create();
        Product::factory()->create(['brand_id' => $brand->id, 'title' => 'Pure Life Filter']);
        Product::factory()->create(['brand_id' => $otherBrand->id, 'title' => 'Other Brand Filter']);

        $this->get(route('catalog.show', $brand->slug))
            ->assertOk()
            ->assertSee('<title>Pure Life</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('catalog.show', $brand->slug).'">', false)
            ->assertSee('Pure Life top brand description.')
            ->assertSee('data-catalog-hero', false)
            ->assertSeeInOrder(['Home', 'Brands', 'Pure Life'])
            ->assertSee('1 product')
            ->assertSee('Pure Life bottom brand description.')
            ->assertSeeInOrder(['Pure Life top brand description.', 'Pure Life Filter', 'Pure Life bottom brand description.'])
            ->assertSee('Pure Life Filter')
            ->assertDontSee('Other Brand Filter');
    }

    public function test_customer_can_browse_only_public_published_products(): void
    {
        $parentCategory = Category::factory()->create(['name' => 'Electronics']);
        Category::factory()->create(['name' => 'Mobile Phones', 'parent_id' => $parentCategory->id]);
        $visible = Product::factory()->create(['title' => 'Visible Water Filter', 'stock_quantity' => 5]);
        Product::factory()->create(['title' => 'Hidden Draft Product', 'status' => 'draft']);

        $this->get(route('storefront.index'))->assertOk()->assertSee($visible->title)->assertSee('id="shop-categories"', false)->assertSee('category-rail', false)->assertSee('Mobile Phones')->assertDontSee('Hidden Draft Product');
        $this->get(route('catalog.show', $visible->slug))->assertOk()->assertSee('কার্টে যোগ করুন');
    }

    public function test_front_page_category_section_includes_a_category_without_products(): void
    {
        $category = Category::factory()->create([
            'name' => 'RO Purifiers',
            'slug' => 'ro-purifiers',
        ]);

        $response = $this->get(route('storefront.index'));

        $response->assertOk()
            ->assertSee('shop-categories')
            ->assertSee('RO Purifiers');
        $this->assertTrue($response->viewData('categories')->contains($category));
    }

    public function test_product_uses_root_slug_and_wins_a_catalog_slug_collision(): void
    {
        Category::factory()->create(['name' => 'Shared Category', 'slug' => 'shared-slug']);
        $product = Product::factory()->create(['title' => 'Shared Product', 'slug' => 'shared-slug']);

        $this->assertStringEndsWith('/shared-slug', route('catalog.show', $product->slug));
        $this->get(route('catalog.show', $product->slug))->assertOk()->assertSee('Shared Product')->assertSee('কার্টে যোগ করুন');
    }

    public function test_old_shop_product_url_permanently_redirects_to_root_slug(): void
    {
        $product = Product::factory()->create(['slug' => 'legacy-product']);

        $this->get(route('storefront.legacy-show', $product))
            ->assertRedirect(route('catalog.show', $product->slug))
            ->assertStatus(301);
    }

    public function test_product_page_shows_breadcrumb_and_action_bar(): void
    {
        $category = Category::factory()->create(['name' => 'Portable Power Station', 'slug' => 'portable-power-station']);
        $brand = Brand::factory()->create(['name' => 'Anker', 'slug' => 'anker']);
        $product = Product::factory()->create(['brand_id' => $brand->id, 'title' => 'Anker SOLIX C300']);
        $product->categories()->attach($category);

        $this->get(route('catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Portable Power Station')
            ->assertSee('Anker')
            ->assertSee('Share:')
            ->assertSee('Add to Compare');
    }

    public function test_product_page_shows_product_information_tabs(): void
    {
        $product = Product::factory()->create([
            'description' => '<p>Detailed product information.</p>',
            'specifications' => [[
                'title' => 'Power Information',
                'items' => [
                    ['title' => 'Battery Type', 'value' => 'LiFePO4'],
                    ['title' => 'Battery Capacity', 'value' => '288Wh'],
                ],
            ]],
            'questions' => [[
                'question' => 'Is 300W enough for everyday devices?',
                'answer' => 'Yes, it supports phones, laptops, lights, and similar devices.',
            ]],
        ]);

        $this->get(route('catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Specification')
            ->assertSee('Description')
            ->assertSee('Questions')
            ->assertSee('Reviews')
            ->assertDontSee('Questions (2)')
            ->assertDontSee('Reviews (1)')
            ->assertSee('href="#product-description"', false)
            ->assertSee('id="product-description"', false)
            ->assertSee('Power Information')
            ->assertSee('Battery Type')
            ->assertSee('LiFePO4')
            ->assertSee('Write a Review')
            ->assertSee('5 out of 5 stars')
            ->assertSee('Is 300W enough for everyday devices?')
            ->assertSee('<details open', false)
            ->assertSee('Detailed product information.');
    }

    public function test_product_page_shows_an_empty_state_when_no_specification_was_provided(): void
    {
        $product = Product::factory()->create(['specifications' => []]);

        $this->get(route('catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('href="#product-specification"', false)
            ->assertSee('id="product-specification"', false)
            ->assertSee('No specifications are available for this product.')
            ->assertDontSee('General Information');
    }

    public function test_each_product_page_shows_only_its_own_questions(): void
    {
        $firstProduct = Product::factory()->create(['questions' => [[
            'question' => 'Does the first product have a warranty?',
            'answer' => 'The first product has a one-year warranty.',
        ]]]);
        $secondProduct = Product::factory()->create(['questions' => [[
            'question' => 'Is the second product waterproof?',
            'answer' => 'The second product is splash resistant.',
        ]]]);

        $this->get(route('catalog.show', $firstProduct->slug))
            ->assertOk()
            ->assertSee('Does the first product have a warranty?')
            ->assertSee('The first product has a one-year warranty.')
            ->assertDontSee('Is the second product waterproof?');

        $this->get(route('catalog.show', $secondProduct->slug))
            ->assertOk()
            ->assertSee('Is the second product waterproof?')
            ->assertDontSee('Does the first product have a warranty?');
    }

    public function test_product_without_questions_shows_an_empty_state(): void
    {
        $product = Product::factory()->create(['questions' => []]);

        $this->get(route('catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('No questions are available for this product yet.');
    }

    public function test_product_page_strikes_through_the_regular_price_for_a_sale_product(): void
    {
        $product = Product::factory()->create(['price' => 5999, 'sale_price' => 4999]);

        $this->get(route('catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('Price:')
            ->assertDontSee('Regular Price:')
            ->assertSee('text-slate-400 line-through', false)
            ->assertSee('5,999');
    }

    public function test_product_gallery_shows_left_thumbnails_only_for_multiple_images(): void
    {
        $singleImageProduct = Product::factory()->create([
            'featured_image_path' => 'products/featured.jpg',
            'gallery_paths' => [],
        ]);
        $multipleImageProduct = Product::factory()->create([
            'featured_image_path' => 'products/featured.jpg',
            'gallery_paths' => ['products/gallery/one.jpg', 'products/gallery/two.jpg'],
        ]);

        $this->get(route('catalog.show', $singleImageProduct->slug))
            ->assertOk()
            ->assertSee('alt="'.e($singleImageProduct->title).'"', false)
            ->assertDontSee('aria-label="View product image', false);

        $this->get(route('catalog.show', $multipleImageProduct->slug))
            ->assertOk()
            ->assertSee('grid-cols-[76px_minmax(0,1fr)]', false)
            ->assertSee('aria-label="View product image 1"', false)
            ->assertSee('aria-label="View product image 3"', false);
    }

    public function test_customer_can_add_product_to_cart_and_place_order(): void
    {
        $product = Product::factory()->create(['title' => 'Smart Filter', 'price' => 1200, 'sale_price' => 999, 'stock_quantity' => 5]);

        $this->post(route('cart.store', $product), ['quantity' => 2])->assertRedirect(route('cart.index'));
        $this->get(route('cart.index'))->assertOk()->assertSee('Smart Filter')->assertSee('1,998.00');
        $response = $this->post(route('checkout.store'), [
            'customer_name' => 'Customer One',
            'customer_phone' => '01700000000',
            'customer_email' => 'customer@example.com',
            'shipping_address' => 'Dhaka, Bangladesh',
            'customer_note' => 'Call before delivery.',
            'delivery_area' => 'dhaka_city',
            'payment_method' => 'cash_on_delivery',
        ]);

        $order = Order::query()->firstOrFail();
        $response->assertRedirect(route('checkout.success', ['order' => $order->order_number]));
        $this->assertDatabaseHas('orders', ['customer_phone' => '01700000000', 'shipping_cost' => 50, 'total' => 2048]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id, 'quantity' => 2, 'line_total' => 1998]);
        $this->assertSame(3, $product->fresh()->stock_quantity);
        $this->assertEmpty(session('cart', []));
    }

    public function test_product_page_shows_order_actions_and_buy_now_opens_checkout(): void
    {
        $product = Product::factory()->create(['title' => 'Smart Filter', 'stock_quantity' => 5]);

        $this->get(route('catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('কার্টে যোগ করুন')
            ->assertSee('অর্ডার করুন')
            ->assertSee('হোয়াটসঅ্যাপে অর্ডার করুন')
            ->assertSee('কল অর্ডার: 01736741793')
            ->assertSee('$dispatch(\'open-modal\', \'direct-order\')', false)
            ->assertSee('অর্ডার করতে নিচের তথ্যগুলি দিন');

        $this->post(route('cart.store', $product), ['quantity' => 2, 'checkout' => true])
            ->assertRedirect(route('checkout.create'));

        $this->assertSame(2, session('cart')[$product->id]);
    }

    public function test_customer_can_place_a_direct_order_with_delivery_charge(): void
    {
        $product = Product::factory()->create(['price' => 1000, 'sale_price' => 900, 'stock_quantity' => 5]);

        $response = $this->post(route('direct-order.store', $product), [
            'customer_name' => 'Direct Customer',
            'customer_phone' => '01700000000',
            'customer_email' => 'direct@example.com',
            'shipping_address' => 'Uttara, Dhaka',
            'customer_note' => 'Call first.',
            'payment_method' => 'cash_on_delivery',
            'quantity' => 2,
            'delivery_area' => 'dhaka_outside',
        ]);

        $order = Order::query()->firstOrFail();
        $response->assertRedirect(route('checkout.success', ['order' => $order->order_number]));
        $this->get(route('checkout.success', ['order' => $order->order_number]))
            ->assertOk()
            ->assertSee($order->order_number);
        $this->assertSame('ORD00001', $order->order_number);
        $this->assertSame('1800.00', $order->subtotal);
        $this->assertSame('80.00', $order->shipping_cost);
        $this->assertSame('1880.00', $order->total);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2, 'line_total' => 1800]);
        $this->assertSame(3, $product->fresh()->stock_quantity);
    }

    public function test_customer_cannot_order_more_than_available_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 1]);

        $this->post(route('cart.store', $product), ['quantity' => 2])->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_missing_storefront_page_uses_the_custom_not_found_design(): void
    {
        $this->get('/this-page-does-not-exist')
            ->assertNotFound()
            ->assertSee('Error 404')
            ->assertSee('Page not found')
            ->assertSee('Back to home')
            ->assertSee('Browse products');
    }
}
