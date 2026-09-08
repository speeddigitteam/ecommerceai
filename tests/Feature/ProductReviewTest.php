<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_customer_reviews_page(): void
    {
        $this->get(route('customer.reviews.index'))->assertRedirect(route('login'));
    }

    public function test_customer_cannot_review_product_without_completed_purchase(): void
    {
        $customer = User::factory()->customer()->create();
        $product = Product::factory()->create();

        $this->actingAs($customer)->post(route('customer.reviews.store', $product), [
            'rating' => 5,
            'body' => 'This product works very well for me.',
        ])->assertForbidden();
    }

    public function test_customer_can_review_completed_purchase_with_images(): void
    {
        Storage::fake('public');
        $customer = User::factory()->customer()->create();
        $product = Product::factory()->create();
        $this->completedOrder($customer, $product);

        $this->actingAs($customer)->post(route('customer.reviews.store', $product), [
            'rating' => 5,
            'body' => 'This product works very well for me.',
            'images' => [UploadedFile::fake()->image('review.jpg')],
        ])->assertRedirect(route('customer.reviews.index'));

        $review = ProductReview::query()->firstOrFail();
        $this->assertSame(5, $review->rating);
        Storage::disk('public')->assertExists($review->image_paths[0]);
    }

    public function test_pending_order_does_not_allow_a_review(): void
    {
        $customer = User::factory()->customer()->create();
        $product = Product::factory()->create();
        $this->completedOrder($customer, $product, 'pending');

        $this->actingAs($customer)->post(route('customer.reviews.store', $product), [
            'rating' => 4,
            'body' => 'This should not be accepted yet.',
        ])->assertForbidden();
    }

    public function test_product_page_displays_dynamic_verified_reviews(): void
    {
        $customer = User::factory()->customer()->create(['name' => 'Review Customer']);
        $product = Product::factory()->create();
        $order = $this->completedOrder($customer, $product);
        ProductReview::factory()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => 4,
            'body' => 'A genuinely useful product for daily work.',
        ]);

        $this->get(route('catalog.show', $product->slug))
            ->assertOk()
            ->assertSee('4.0')
            ->assertSee('A genuinely useful product for daily work.')
            ->assertSee('Verified purchase');
    }

    public function test_customer_can_update_review_and_remove_an_image(): void
    {
        Storage::fake('public');
        $customer = User::factory()->customer()->create();
        $product = Product::factory()->create();
        $order = $this->completedOrder($customer, $product);
        Storage::disk('public')->put('reviews/old.jpg', 'image');
        $review = ProductReview::factory()->create([
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
            'image_paths' => ['reviews/old.jpg'],
        ]);

        $this->actingAs($customer)->put(route('customer.reviews.update', $review), [
            'rating' => 3,
            'body' => 'Updated review after using it longer.',
            'remove_images' => ['reviews/old.jpg'],
        ])->assertRedirect(route('customer.reviews.index'));

        $this->assertSame([], $review->fresh()->image_paths);
        Storage::disk('public')->assertMissing('reviews/old.jpg');
    }

    public function test_customer_cannot_update_another_customers_review(): void
    {
        $owner = User::factory()->customer()->create();
        $otherCustomer = User::factory()->customer()->create();
        $product = Product::factory()->create();
        $order = $this->completedOrder($owner, $product);
        $review = ProductReview::factory()->create([
            'user_id' => $owner->id,
            'product_id' => $product->id,
            'order_id' => $order->id,
        ]);

        $this->actingAs($otherCustomer)->put(route('customer.reviews.update', $review), [
            'rating' => 1,
            'body' => 'This update must never be accepted.',
        ])->assertForbidden();
    }

    private function completedOrder(User $customer, Product $product, string $status = 'completed'): Order
    {
        $order = Order::query()->create([
            'user_id' => $customer->id,
            'order_number' => 'ORD'.fake()->unique()->numerify('#####'),
            'customer_name' => $customer->name,
            'customer_phone' => '01700000000',
            'customer_email' => $customer->email,
            'shipping_address' => 'Dhaka',
            'subtotal' => 100,
            'shipping_cost' => 0,
            'total' => 100,
            'payment_method' => 'cash_on_delivery',
            'status' => $status,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_title' => $product->title,
            'sku' => $product->sku,
            'unit_price' => 100,
            'quantity' => 1,
            'line_total' => 100,
        ]);

        return $order;
    }
}
