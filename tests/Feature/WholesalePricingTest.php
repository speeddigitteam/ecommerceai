<?php

namespace Tests\Feature;

use App\Http\Controllers\CartController;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WholesalePriceTier;
use App\Services\WholesalePricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class WholesalePricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_approved_wholesalers_receive_quantity_tier_prices(): void
    {
        $product = Product::factory()->create(['price' => 500, 'sale_price' => null]);
        WholesalePriceTier::query()->create(['product_id' => $product->id, 'minimum_quantity' => 10, 'unit_price' => 430]);
        WholesalePriceTier::query()->create(['product_id' => $product->id, 'minimum_quantity' => 25, 'unit_price' => 400]);
        $product->load('wholesalePriceTiers');
        $pricing = app(WholesalePricing::class);

        $retail = User::factory()->customer()->create(['wholesale_status' => 'retail']);
        $wholesaler = User::factory()->customer()->create(['wholesale_status' => 'approved', 'business_name' => 'Acme Traders']);

        $this->assertSame(500.0, $pricing->resolve($product, null, 25, $retail)['unit_price']);
        $this->assertSame(430.0, $pricing->resolve($product, null, 10, $wholesaler)['unit_price']);
        $this->assertSame(400.0, $pricing->resolve($product, null, 25, $wholesaler)['unit_price']);
        $this->assertSame('wholesale', $pricing->resolve($product, null, 25, $wholesaler)['pricing_type']);
    }

    public function test_cart_uses_server_calculated_wholesale_price(): void
    {
        $customer = User::factory()->customer()->create(['wholesale_status' => 'approved', 'business_name' => 'Acme Traders']);
        $product = Product::factory()->create(['price' => 500, 'sale_price' => null, 'stock_quantity' => 100]);
        WholesalePriceTier::query()->create(['product_id' => $product->id, 'minimum_quantity' => 10, 'unit_price' => 430]);

        $request = Request::create('/cart');
        $request->setLaravelSession($this->app['session']->driver());
        $request->setUserResolver(fn (): User => $customer);
        $request->session()->put('cart', [(string) $product->id => 10]);

        $cart = app(CartController::class)->cartData($request);

        $this->assertSame(4300.0, $cart['subtotal']);
        $this->assertSame('wholesale', $cart['items']->first()['pricingType']);
        $this->assertSame(430.0, $cart['items']->first()['unitPrice']);
    }

    public function test_checkout_persists_wholesale_order_and_item_price_snapshot(): void
    {
        $customer = User::factory()->customer()->create(['wholesale_status' => 'approved', 'business_name' => 'Acme Traders']);
        $product = Product::factory()->create(['price' => 500, 'sale_price' => null, 'stock_quantity' => 100]);
        WholesalePriceTier::query()->create(['product_id' => $product->id, 'minimum_quantity' => 10, 'unit_price' => 430]);

        $this->actingAs($customer)->withSession(['cart' => [(string) $product->id => 10]])->post(route('checkout.store'), [
            'customer_name' => 'Wholesale Buyer',
            'customer_phone' => '01700000000',
            'customer_email' => $customer->email,
            'shipping_address' => 'Dhaka',
            'delivery_area' => 'dhaka_city',
            'payment_method' => 'cash_on_delivery',
        ])->assertRedirect();

        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertSame('wholesale', $order->order_type);
        $this->assertSame('4300.00', $order->subtotal);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'unit_price' => 430,
            'pricing_type' => 'wholesale',
            'quantity' => 10,
            'line_total' => 4300,
        ]);
    }

    public function test_admin_can_save_product_wholesale_tiers(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['price' => 500, 'stock_quantity' => 100]);

        $this->actingAs($admin)->put(route('products.update', $product), [
            'title' => $product->title,
            'type' => 'physical',
            'price' => 500,
            'stock_quantity' => 100,
            'status' => 'published',
            'visibility' => 'public',
            'wholesale_tiers' => [
                ['minimum_quantity' => 10, 'unit_price' => 430],
                ['minimum_quantity' => 25, 'unit_price' => 400],
                ['minimum_quantity' => null, 'unit_price' => null],
            ],
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('wholesale_price_tiers', [
            'product_id' => $product->id,
            'minimum_quantity' => 10,
            'unit_price' => 430,
        ]);
        $this->assertSame(2, $product->wholesalePriceTiers()->count());
    }
}
