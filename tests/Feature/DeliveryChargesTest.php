<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\Services\DeliveryCharges;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryChargesTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_free_custom_and_digital_products_use_highest_charge(): void
    {
        WebsiteSetting::factory()->create(['delivery_charges' => ['dhaka_city' => 65, 'dhaka_outside' => 95, 'outside_dhaka' => 125]]);
        $normal = Product::factory()->create();
        $free = Product::factory()->create(['delivery_charge_type' => 'free']);
        $custom = Product::factory()->create(['delivery_charge_type' => 'custom', 'delivery_charges' => ['dhaka_city' => 30, 'dhaka_outside' => 150, 'outside_dhaka' => 180]]);
        $digital = Product::factory()->digital()->create();
        $service = app(DeliveryCharges::class);
        $this->assertSame(['dhaka_city' => 65.0, 'dhaka_outside' => 150.0, 'outside_dhaka' => 180.0], $service->rates([$normal, $free, $custom, $digital]));
        $this->assertSame(['dhaka_city' => 0.0, 'dhaka_outside' => 0.0, 'outside_dhaka' => 0.0], $service->rates([$free, $digital]));
        $this->assertSame($service->rates([$custom]), $service->rates([$custom, $custom]));
    }

    public function test_settings_require_admin_and_validate_rates(): void
    {
        $this->get(route('settings.delivery-charges.edit'))->assertRedirect(route('admin.login'));
        $admin = User::factory()->create();
        $this->actingAs($admin)->get(route('settings.delivery-charges.edit'))->assertOk()->assertSee('Delivery Charge Settings');
        $rates = ['dhaka_city' => 60, 'dhaka_outside' => 90, 'outside_dhaka' => 120];
        $this->put(route('settings.delivery-charges.update'), ['delivery_charges' => $rates])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertEquals($rates, WebsiteSetting::firstOrFail()->delivery_charges);
        $this->put(route('settings.delivery-charges.update'), ['delivery_charges' => ['dhaka_city' => -1]])->assertSessionHasErrors(['delivery_charges.dhaka_city', 'delivery_charges.dhaka_outside']);
    }

    public function test_checkout_uses_highest_charge_and_preserves_saved_order(): void
    {
        $free = Product::factory()->create(['delivery_charge_type' => 'free', 'price' => 100, 'stock_quantity' => 10]);
        $custom = Product::factory()->create(['delivery_charge_type' => 'custom', 'delivery_charges' => ['dhaka_city' => 75.50, 'dhaka_outside' => 95, 'outside_dhaka' => 125], 'price' => 200, 'stock_quantity' => 10]);
        $cart = [$free->id => 2, $custom->id => 3];
        $this->withSession(['cart' => $cart])->get(route('checkout.create'))->assertOk()->assertSee('75.5');
        $this->withSession(['cart' => $cart])->post(route('checkout.store'), $this->details() + ['shipping_cost' => 0])->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertSame('75.50', $order->shipping_cost);
        $this->assertSame('875.50', $order->total);
        $this->assertSame('dhaka_city', $order->delivery_area);
        $custom->update(['delivery_charge_type' => 'free']);
        WebsiteSetting::factory()->create(['delivery_charges' => ['dhaka_city' => 999, 'dhaka_outside' => 999, 'outside_dhaka' => 999]]);
        $this->assertSame('75.50', $order->fresh()->shipping_cost);
    }

    public function test_direct_order_and_product_page_support_free_and_custom_delivery(): void
    {
        foreach (['free' => 0, 'custom' => 42.50, 'default' => 50] as $type => $expected) {
            $product = Product::factory()->create(['delivery_charge_type' => $type, 'delivery_charges' => ['dhaka_city' => 42.5, 'dhaka_outside' => 82, 'outside_dhaka' => 102], 'stock_quantity' => 10]);
            $this->get(route('catalog.show', $product->slug))->assertOk()->assertSee($type === 'free' ? 'Free Delivery' : 'Delivery charge');
            $this->post(route('direct-order.store', $product), $this->details() + ['quantity' => 2])->assertSessionHasNoErrors()->assertRedirect();
            $this->assertEquals($expected, Order::latest('id')->firstOrFail()->shipping_cost);
        }
    }

    public function test_product_delivery_options_are_saved_and_custom_rates_are_required(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create();
        $data = ['title' => $product->title, 'type' => 'physical', 'stock_quantity' => 10, 'status' => 'published', 'visibility' => 'public', 'delivery_charge_type' => 'custom'];
        $this->put(route('products.update', $product), $data)->assertSessionHasErrors('delivery_charges.dhaka_city');
        $data['delivery_charges'] = ['dhaka_city' => 0, 'dhaka_outside' => 45, 'outside_dhaka' => 65];
        $this->put(route('products.update', $product), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('custom', $product->fresh()->delivery_charge_type);
        $this->assertEquals($data['delivery_charges'], $product->fresh()->delivery_charges);
        $this->get(route('products.edit', $product))->assertOk()->assertSee('Custom charges');
    }

    public function test_admin_order_uses_custom_rates_and_saved_area_filter(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['delivery_charge_type' => 'custom', 'delivery_charges' => ['dhaka_city' => 12, 'dhaka_outside' => 22, 'outside_dhaka' => 32], 'stock_quantity' => 10]);
        $this->actingAs($admin)->get(route('orders.create'))->assertOk();
        $this->post(route('orders.store'), $this->details() + ['product_id' => $product->id, 'quantity' => 2])->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertSame('12.00', $order->shipping_cost);
        $this->assertSame('dhaka_city', $order->delivery_area);
        $this->get(route('orders.index', ['delivery_area' => 'dhaka_city']))->assertOk()->assertSee($order->order_number);
        $this->get(route('orders.index', ['delivery_area' => 'outside_dhaka']))->assertOk()->assertViewHas('orders', fn ($orders) => $orders->isEmpty());
    }

    private function details(): array
    {
        return ['customer_name' => 'Delivery Customer', 'customer_phone' => '01700000000', 'shipping_address' => 'Dhaka', 'delivery_area' => 'dhaka_city', 'payment_method' => 'cash_on_delivery'];
    }
}
