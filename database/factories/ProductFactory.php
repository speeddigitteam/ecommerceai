<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function configure(): static
    {
        return $this->afterCreating(function (Product $product): void {
            $product->categories()->attach(Category::factory()->create());
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'brand_id' => Brand::factory(),
            'unit_id' => Unit::factory(),
            'type' => 'physical',
            'title' => $title,
            'slug' => str($title)->slug(),
            'sku' => fake()->unique()->bothify('SKU-####'),
            'description' => fake()->paragraphs(3, true),
            'short_description' => fake()->sentence(),
            'specifications' => [],
            'questions' => [],
            'price' => fake()->randomFloat(2, 10, 1000),
            'sale_price' => null,
            'stock_quantity' => fake()->numberBetween(0, 100),
            'status' => 'published',
            'visibility' => 'public',
            'published_at' => now(),
            'gallery_paths' => [],
            'tags' => fake()->words(3),
            'focus_keyword' => str($title)->words(2)->toString(),
            'seo_title' => str($title)->limit(60, ''),
            'meta_description' => fake()->sentence(15),
        ];
    }

    /** @return static */
    public function digital()
    {
        return $this->state(fn (array $attributes): array => [
            'type' => 'digital',
            'stock_quantity' => Product::UNLIMITED_STOCK,
            'digital_file_path' => 'digital-products/sample.pdf',
            'digital_file_name' => 'sample.pdf',
        ]);
    }
}
