<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Models\WholesalePriceTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_product_management(): void
    {
        $this->get(route('products.index'))->assertRedirect(route('admin.login'));
    }

    public function test_user_can_view_product_list_and_form(): void
    {
        $user = User::factory()->create();
        WebsiteSetting::factory()->create(['site_name' => 'Acme Store']);
        Product::factory()->create(['title' => 'Premium Water Purifier']);

        $this->actingAs($user)->get(route('products.index'))->assertOk()->assertSee('Premium Water Purifier');
        $this->actingAs($user)->get(route('products.create'))
            ->assertOk()
            ->assertSee('Add new product')
            ->assertSee('Add new category')
            ->assertSee('Add new brand')
            ->assertSee('Save category')
            ->assertSee('id="product-seo-score"', false)
            ->assertSee('id="focus-keyword-feedback"', false)
            ->assertSee('id="focus-keyword-input"', false)
            ->assertSee('0 of 11 checks passed')
            ->assertSee('id="quick-taxonomy-modal"', false)
            ->assertSee('id="quick-category-slug"', false)
            ->assertSee('id="quick-category-description"', false)
            ->assertSee('Add section')
            ->assertSee('Add title and value')
            ->assertSee('</span> | Acme Store', false);
    }

    public function test_user_can_quick_create_category_and_brand_from_product_form(): void
    {
        $user = User::factory()->create();
        $parent = Category::factory()->create(['name' => 'Electronics']);

        $this->actingAs($user)->postJson(route('categories.store'), [
            'name' => 'Mobile Phones',
            'slug' => 'smart-mobile-phones',
            'parent_id' => $parent->id,
            'description' => 'Browse the latest mobile phones.',
        ])->assertCreated()
            ->assertJsonPath('category.name', 'Mobile Phones')
            ->assertJsonPath('category.slug', 'smart-mobile-phones')
            ->assertJsonPath('category.parent_id', $parent->id);

        $this->actingAs($user)->postJson(route('brands.store'), [
            'name' => 'Apple',
            'slug' => 'apple-devices',
        ])->assertCreated()
            ->assertJsonPath('brand.name', 'Apple')
            ->assertJsonPath('brand.slug', 'apple-devices');

        $this->assertDatabaseHas('categories', [
            'name' => 'Mobile Phones',
            'slug' => 'smart-mobile-phones',
            'parent_id' => $parent->id,
            'description' => 'Browse the latest mobile phones.',
        ]);
        $this->assertDatabaseHas('brands', ['name' => 'Apple', 'slug' => 'apple-devices']);
    }

    public function test_quick_create_returns_validation_errors_as_json(): void
    {
        $user = User::factory()->create();
        Brand::factory()->create(['name' => 'Apple']);

        $this->actingAs($user)->postJson(route('brands.store'), ['name' => 'Apple'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_user_can_open_and_update_a_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create([
            'name' => 'Water Filters',
            'slug' => 'water-filters',
        ]);

        $this->actingAs($user)->get(route('categories.edit', $category))
            ->assertOk()
            ->assertSee('Edit category')
            ->assertSee('Water Filters');

        $this->actingAs($user)->put(route('categories.update', $category), [
            'name' => 'RO Purifiers',
            'slug' => 'ro-purifiers',
            'description' => 'Reverse osmosis water purifiers.',
        ])->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'RO Purifiers',
            'slug' => 'ro-purifiers',
        ]);
    }

    public function test_public_product_page_outputs_product_seo_metadata(): void
    {
        WebsiteSetting::factory()->create(['site_name' => 'Acme Store']);
        $product = Product::factory()->create([
            'title' => 'Smart Water Purifier',
            'slug' => 'smart-water-purifier',
            'status' => 'published',
            'visibility' => 'public',
            'seo_title' => 'Best Smart Water Purifier',
            'meta_description' => 'Clean drinking water for every home.',
        ]);

        $this->get(route('catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('<title>Best Smart Water Purifier | Acme Store</title>', false)
            ->assertSee('<meta name="description" content="Clean drinking water for every home.">', false)
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('<link rel="canonical" href="'.route('catalog.show', $product->slug).'">', false);
    }

    public function test_non_public_product_is_not_available_on_catalog_route(): void
    {
        $product = Product::factory()->create([
            'status' => 'draft',
            'visibility' => 'public',
        ]);

        $this->get(route('catalog.show', $product->slug))->assertNotFound();
    }

    public function test_user_can_create_product_with_organization_and_media(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $secondaryCategory = Category::factory()->create();
        $brand = Brand::factory()->create();
        $unit = Unit::factory()->create();

        $response = $this->actingAs($user)->post(route('products.store'), [
            'title' => 'Smart Water Purifier',
            'description' => 'A complete product description.',
            'short_description' => 'Clean water for every home.',
            'specifications' => [
                [
                    'title' => 'General Information',
                    'items' => [
                        ['title' => 'Filter Stages', 'value' => 'Six'],
                        ['title' => 'Capacity', 'value' => '12 litres'],
                    ],
                ],
                [
                    'title' => 'Warranty',
                    'items' => [
                        ['title' => 'Coverage', 'value' => 'Two years'],
                    ],
                ],
            ],
            'questions' => [
                ['question' => 'Does it include a warranty?', 'answer' => 'Yes, it includes a two-year warranty.'],
                ['question' => 'Is installation included?', 'answer' => 'Installation can be arranged separately.'],
            ],
            'category_ids' => [$category->id, $secondaryCategory->id],
            'brand_id' => $brand->id,
            'unit_id' => $unit->id,
            'sku' => 'WP-100',
            'type' => 'physical',
            'price' => '1200.00',
            'sale_price' => '999.00',
            'stock_quantity' => 12,
            'status' => 'published',
            'visibility' => 'public',
            'tags' => 'water, featured, home',
            'focus_keyword' => 'smart water purifier, clean drinking water',
            'seo_title' => 'Smart Water Purifier for Home',
            'meta_description' => 'A reliable smart water purifier designed to provide clean drinking water for every home.',
            'featured_image' => UploadedFile::fake()->image('product.jpg'),
            'gallery' => [UploadedFile::fake()->image('gallery.jpg')],
            'video' => UploadedFile::fake()->create('product.mp4', 100, 'video/mp4'),
        ]);

        $response->assertRedirect(route('products.index'));
        $product = Product::query()->where('sku', 'WP-100')->firstOrFail();
        $this->assertSame('smart-water-purifier', $product->slug);
        $this->assertSame(['water', 'featured', 'home'], $product->tags);
        $this->assertSame('smart water purifier, clean drinking water', $product->focus_keyword);
        $this->assertSame('General Information', $product->specifications[0]['title']);
        $this->assertSame('12 litres', $product->specifications[0]['items'][1]['value']);
        $this->assertSame('Warranty', $product->specifications[1]['title']);
        $this->assertSame('Does it include a warranty?', $product->questions[0]['question']);
        $this->assertSame('Installation can be arranged separately.', $product->questions[1]['answer']);
        $this->assertTrue($product->categories->contains($category));
        $this->assertTrue($product->categories->contains($secondaryCategory));
        $this->assertFalse(Schema::hasColumn('products', 'category_id'));
        $this->assertNotNull($product->published_at);
        Storage::disk('public')->assertExists($product->featured_image_path);
        Storage::disk('public')->assertExists($product->video_path);
        Storage::disk('public')->assertExists($product->gallery_paths[0]);
    }

    public function test_user_can_update_preview_and_delete_product(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $product = Product::factory()->create(['status' => 'draft']);

        $this->actingAs($user)->put(route('products.update', $product), [
            'title' => 'Updated Product',
            'slug' => 'updated-product',
            'type' => 'physical',
            'stock_quantity' => 4,
            'status' => 'published',
            'visibility' => 'private',
            'tags' => 'updated',
        ])->assertRedirect(route('products.index'));

        $this->actingAs($user)->get(route('products.show', $product))->assertOk()->assertSee('Updated Product');
        $this->actingAs($user)->delete(route('products.destroy', $product))->assertRedirect(route('products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_user_can_duplicate_a_complete_product_as_a_draft(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('products/purifier.webp', 'image');
        Storage::disk('public')->put('products/gallery/purifier.webp', 'image');
        Storage::disk('public')->put('products/variants/red.webp', 'image');
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'title' => 'Premium Water Purifier',
            'slug' => 'premium-water-purifier',
            'sku' => 'WP-100',
            'status' => 'published',
            'published_at' => now(),
            'featured_image_path' => 'products/purifier.webp',
            'gallery_paths' => ['products/gallery/purifier.webp'],
            'delivery_charge_type' => 'custom',
            'delivery_charges' => ['dhaka_city' => 50, 'dhaka_outside' => 80, 'outside_dhaka' => 100],
        ]);
        $product->categories()->attach($category);
        $variant = ProductVariant::factory()->for($product)->create([
            'sku' => 'WP-100-RED',
            'image_path' => 'products/variants/red.webp',
            'options' => ['Color' => 'Red'],
        ]);
        WholesalePriceTier::query()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'minimum_quantity' => 5,
            'unit_price' => 900,
        ]);

        $response = $this->actingAs($user)->post(route('products.duplicate', $product));

        $duplicate = Product::query()->whereKeyNot($product->id)->sole();
        $response->assertRedirect(route('products.edit', $duplicate));
        $this->assertSame('Premium Water Purifier - Copy', $duplicate->title);
        $this->assertSame('premium-water-purifier-copy', $duplicate->slug);
        $this->assertSame('WP-100-COPY', $duplicate->sku);
        $this->assertSame('draft', $duplicate->status);
        $this->assertNull($duplicate->published_at);
        $this->assertSame($product->featured_image_path, $duplicate->featured_image_path);
        $this->assertSame($product->delivery_charges, $duplicate->delivery_charges);
        $this->assertTrue($duplicate->categories->contains($category));
        $this->assertSame('WP-100-RED-COPY', $duplicate->variants->sole()->sku);
        $this->assertSame($duplicate->variants->sole()->id, $duplicate->wholesalePriceTiers->sole()->product_variant_id);
        $this->assertSame('published', $product->fresh()->status);

        $this->actingAs($user)->delete(route('products.destroy', $product));
        Storage::disk('public')->assertExists('products/purifier.webp');
        Storage::disk('public')->assertExists('products/gallery/purifier.webp');
        Storage::disk('public')->assertExists('products/variants/red.webp');
    }

    public function test_user_can_edit_dynamic_product_specifications(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'specifications' => [[
                'title' => 'Power',
                'items' => [['title' => 'Capacity', 'value' => '288Wh']],
            ]],
        ]);

        $this->actingAs($user)->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('Power')
            ->assertSee('288Wh');

        $this->actingAs($user)->put(route('products.update', $product), [
            'title' => $product->title,
            'type' => 'physical',
            'stock_quantity' => $product->stock_quantity,
            'status' => $product->status,
            'visibility' => $product->visibility,
            'specifications' => [[
                'title' => 'Battery Information',
                'items' => [
                    ['title' => 'Capacity', 'value' => '300Wh'],
                    ['title' => 'Cycle Life', 'value' => '3000 cycles'],
                ],
            ]],
        ])->assertRedirect(route('products.index'));

        $product->refresh();

        $this->assertSame('Battery Information', $product->specifications[0]['title']);
        $this->assertSame('3000 cycles', $product->specifications[0]['items'][1]['value']);
    }

    public function test_user_can_resave_a_product_and_change_an_existing_variants_price_and_sku(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->for($product)->create([
            'sku' => 'ORIGINAL-SKU',
            'price' => 1200,
            'sale_price' => null,
            'stock_quantity' => 5,
            'options' => ['Color' => 'Red'],
        ]);

        $this->actingAs($user)->put(route('products.update', $product), [
            'title' => $product->title,
            'type' => 'physical',
            'stock_quantity' => $product->stock_quantity,
            'status' => $product->status,
            'visibility' => $product->visibility,
            'variants' => [[
                'id' => $variant->id,
                'sku' => 'ORIGINAL-SKU',
                'price' => 1500,
                'sale_price' => 1300,
                'stock_quantity' => 8,
                'options' => [['name' => 'Color', 'value' => 'Red']],
            ]],
        ])->assertRedirect(route('products.index'));

        $variant->refresh();

        $this->assertSame('ORIGINAL-SKU', $variant->sku);
        $this->assertSame('1500.00', $variant->price);
        $this->assertSame('1300.00', $variant->sale_price);
        $this->assertSame(8, $variant->stock_quantity);
    }

    public function test_two_variants_keep_their_own_sku_and_price(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->put(route('products.update', $product), [
            'title' => $product->title,
            'type' => 'physical',
            'stock_quantity' => $product->stock_quantity,
            'status' => $product->status,
            'visibility' => $product->visibility,
            'variants' => [
                [
                    'sku' => 'VARIANT-RED',
                    'price' => 1200,
                    'stock_quantity' => 5,
                    'options' => [['name' => 'Color', 'value' => 'Red']],
                ],
                [
                    'sku' => 'VARIANT-BLUE',
                    'price' => 1500,
                    'stock_quantity' => 3,
                    'options' => [['name' => 'Color', 'value' => 'Blue']],
                ],
            ],
        ])->assertRedirect(route('products.index'));

        $product->refresh();

        $this->assertCount(2, $product->variants);
        $this->assertSame('VARIANT-RED', $product->variants[0]->sku);
        $this->assertSame('1200.00', $product->variants[0]->price);
        $this->assertSame('VARIANT-BLUE', $product->variants[1]->sku);
        $this->assertSame('1500.00', $product->variants[1]->price);

        $this->get(route('catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('VARIANT-RED', false)
            ->assertSee('VARIANT-BLUE', false);
    }
}
