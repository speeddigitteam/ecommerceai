<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DigitalProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_digital_product_with_a_file_and_stock_is_unlimited(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('products.store'), [
            'title' => 'Ebook: Laravel Basics',
            'type' => 'digital',
            'price' => '500.00',
            'status' => 'published',
            'visibility' => 'public',
            'digital_file' => UploadedFile::fake()->create('ebook.pdf', 500, 'application/pdf'),
        ]);

        $response->assertRedirect(route('products.index'));
        $product = Product::query()->where('title', 'Ebook: Laravel Basics')->firstOrFail();
        $this->assertSame('digital', $product->type);
        $this->assertTrue($product->isDigital());
        $this->assertSame(Product::UNLIMITED_STOCK, $product->stock_quantity);
        $this->assertSame('ebook.pdf', $product->digital_file_name);
        Storage::disk('local')->assertExists($product->digital_file_path);
    }

    public function test_digital_product_requires_a_file_to_be_uploaded(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('products.store'), [
            'title' => 'Ebook Without File',
            'type' => 'digital',
            'price' => '500.00',
            'status' => 'published',
            'visibility' => 'public',
        ]);

        $response->assertSessionHasErrors('digital_file');
        $this->assertDatabaseMissing('products', ['title' => 'Ebook Without File']);
    }

    public function test_customer_can_buy_a_digital_product_without_a_shipping_address(): void
    {
        $product = Product::factory()->digital()->create(['title' => 'Digital License', 'price' => 800]);

        $this->post(route('cart.store', $product), ['quantity' => 1])->assertRedirect(route('cart.index'));
        $response = $this->post(route('checkout.store'), [
            'customer_name' => 'Digital Buyer',
            'customer_phone' => '01700000000',
            'payment_method' => 'cash_on_delivery',
        ]);

        $order = Order::query()->firstOrFail();
        $response->assertRedirect(route('checkout.success', ['order' => $order->order_number]));
        $this->assertDatabaseHas('orders', ['customer_phone' => '01700000000', 'shipping_cost' => 0, 'total' => 800]);
        $this->assertSame(Product::UNLIMITED_STOCK, $product->fresh()->stock_quantity);
    }

    public function test_mixed_cart_with_a_physical_item_still_requires_shipping_address(): void
    {
        $digital = Product::factory()->digital()->create(['price' => 800]);
        $physical = Product::factory()->create(['price' => 500, 'stock_quantity' => 5]);

        $this->post(route('cart.store', $digital), ['quantity' => 1]);
        $this->post(route('cart.store', $physical), ['quantity' => 1]);

        $this->post(route('checkout.store'), [
            'customer_name' => 'Mixed Buyer',
            'customer_phone' => '01700000000',
            'payment_method' => 'cash_on_delivery',
        ])->assertSessionHasErrors(['shipping_address', 'delivery_area']);
    }

    public function test_signed_link_downloads_the_digital_file_and_a_tampered_link_is_rejected(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('digital-products/ebook.pdf', 'file contents');
        $product = Product::factory()->digital()->create(['digital_file_path' => 'digital-products/ebook.pdf', 'digital_file_name' => 'ebook.pdf']);
        $order = $this->createOrder();
        $item = $order->items()->create(['product_id' => $product->id, 'product_title' => $product->title, 'unit_price' => $product->price, 'quantity' => 1, 'line_total' => $product->price]);

        $url = URL::temporarySignedRoute('downloads.show', now()->addDay(), ['order' => $order->id, 'item' => $item->id]);
        $this->get($url)->assertOk();

        $this->get($url.'&tampered=1')->assertForbidden();
    }

    public function test_download_is_rejected_for_a_non_digital_order_item(): void
    {
        $product = Product::factory()->create();
        $order = $this->createOrder();
        $item = $order->items()->create(['product_id' => $product->id, 'product_title' => $product->title, 'unit_price' => $product->price, 'quantity' => 1, 'line_total' => $product->price]);

        $url = URL::temporarySignedRoute('downloads.show', now()->addDay(), ['order' => $order->id, 'item' => $item->id]);
        $this->get($url)->assertNotFound();
    }

    private function createOrder(): Order
    {
        return Order::query()->create([
            'order_number' => 'ORD'.random_int(10000, 99999),
            'customer_name' => 'Test Customer',
            'customer_phone' => '01700000000',
            'shipping_address' => '',
            'subtotal' => 800,
            'shipping_cost' => 0,
            'total' => 800,
            'payment_method' => 'cash_on_delivery',
            'status' => 'pending',
        ]);
    }
}
