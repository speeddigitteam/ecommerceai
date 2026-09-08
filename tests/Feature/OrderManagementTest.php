<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_orders_page(): void
    {
        $this->get(route('orders.index'))->assertRedirect(route('admin.login'));
    }

    public function test_authenticated_user_can_view_confirmed_customer_orders(): void
    {
        $admin = User::factory()->create();
        $order = Order::query()->create([
            'order_number' => 'ORD-260825-ABC123',
            'customer_name' => 'Rahim Ahmed',
            'customer_phone' => '01700000000',
            'customer_email' => 'rahim@example.com',
            'shipping_address' => 'Uttara, Dhaka',
            'subtotal' => 1000,
            'shipping_cost' => 50,
            'total' => 1050,
            'payment_method' => 'cash_on_delivery',
            'status' => 'pending',
        ]);
        $order->items()->create([
            'product_title' => 'Test Product',
            'unit_price' => 1000,
            'quantity' => 1,
            'line_total' => 1000,
        ]);

        $this->actingAs($admin)->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Orders Management')
            ->assertSee('ORD-260825-ABC123')
            ->assertSee('Rahim Ahmed')
            ->assertSee('Test Product')
            ->assertSee('1,050.00');
    }

    public function test_admin_can_open_a_printable_parcel_label_with_barcode_and_sender_details(): void
    {
        $admin = User::factory()->create();
        WebsiteSetting::factory()->create([
            'site_name' => 'Label Shop',
            'business_phone' => '01711111111',
            'business_address' => 'Uttara, Dhaka',
            'website_url' => 'https://label-shop.test',
        ]);
        $order = Order::query()->create([
            'order_number' => 'ORD00001', 'customer_name' => 'Parcel Customer', 'customer_phone' => '01822222222',
            'shipping_address' => 'Dhanmondi, Dhaka', 'subtotal' => 500, 'shipping_cost' => 50, 'total' => 550,
            'payment_method' => 'cash_on_delivery', 'status' => 'pending',
        ]);
        $order->items()->create(['product_title' => 'Parcel Product', 'unit_price' => 500, 'quantity' => 1, 'line_total' => 500]);

        $this->get(route('orders.print-label', $order))->assertRedirect(route('admin.login'));

        $this->actingAs($admin)->get(route('orders.print-label', $order))
            ->assertOk()
            ->assertSee('Label Shop')
            ->assertSee('01711111111')
            ->assertSee('Parcel Customer')
            ->assertSee('ORD00001')
            ->assertSee('aria-label="Barcode ORD00001"', false)
            ->assertSee('@page{size:4in 6in', false);
    }

    public function test_authenticated_user_can_view_and_update_an_order(): void
    {
        $admin = User::factory()->create();
        $order = Order::query()->create([
            'order_number' => 'ORD00001',
            'customer_name' => 'Original Customer',
            'customer_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
            'subtotal' => 500,
            'shipping_cost' => 50,
            'total' => 550,
            'payment_method' => 'cash_on_delivery',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('ORD00001')
            ->assertSee('Original Customer');
        $this->assertNotNull($order->fresh()->viewed_at);

        $this->actingAs($admin)->put(route('orders.update', $order), [
            'customer_name' => 'Updated Customer',
            'customer_phone' => '01800000000',
            'customer_email' => 'updated@example.com',
            'shipping_address' => 'Chattogram',
            'customer_note' => 'Handle carefully.',
        ])->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_name' => 'Updated Customer',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_increase_and_decrease_order_item_quantity_with_stock_and_totals_updated(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['price' => 200, 'sale_price' => null, 'stock_quantity' => 8]);
        $order = Order::query()->create([
            'order_number' => 'ORD00001', 'customer_name' => 'Quantity Customer', 'customer_phone' => '01700000000',
            'shipping_address' => 'Dhaka', 'subtotal' => 400, 'shipping_cost' => 50, 'total' => 450,
            'payment_method' => 'cash_on_delivery', 'status' => 'pending',
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id, 'product_title' => $product->title, 'sku' => $product->sku,
            'unit_price' => 200, 'quantity' => 2, 'line_total' => 400,
        ]);

        $payload = [
            'customer_name' => $order->customer_name, 'customer_phone' => $order->customer_phone,
            'shipping_address' => $order->shipping_address, 'items' => [['id' => $item->id, 'quantity' => 5]],
        ];
        $this->actingAs($admin)->put(route('orders.update', $order), $payload)
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(5, $item->fresh()->quantity);
        $this->assertSame(5, $product->fresh()->stock_quantity);
        $this->assertSame('1050.00', $order->fresh()->total);

        $payload['items'][0]['quantity'] = 1;
        $this->actingAs($admin)->put(route('orders.update', $order), $payload)
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(1, $item->fresh()->quantity);
        $this->assertSame(9, $product->fresh()->stock_quantity);
        $this->assertSame('250.00', $order->fresh()->total);
    }

    public function test_order_item_quantity_cannot_exceed_stock_or_change_after_shipment(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 1]);
        $order = Order::query()->create([
            'order_number' => 'ORD00001', 'customer_name' => 'Protected Customer', 'customer_phone' => '01700000000',
            'shipping_address' => 'Dhaka', 'subtotal' => 100, 'shipping_cost' => 50, 'total' => 150,
            'payment_method' => 'cash_on_delivery', 'status' => 'pending',
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id, 'product_title' => $product->title,
            'unit_price' => 100, 'quantity' => 1, 'line_total' => 100,
        ]);

        $payload = [
            'customer_name' => $order->customer_name, 'customer_phone' => $order->customer_phone,
            'shipping_address' => $order->shipping_address, 'items' => [['id' => $item->id, 'quantity' => 3]],
        ];

        $this->actingAs($admin)->put(route('orders.update', $order), $payload)
            ->assertSessionHasErrors('items');

        $order->update(['status' => 'shipped']);
        $payload['items'][0]['quantity'] = 2;
        $this->actingAs($admin)->put(route('orders.update', $order), $payload)
            ->assertSessionHasErrors('items');

        $this->assertSame(1, $item->fresh()->quantity);
        $this->assertSame(1, $product->fresh()->stock_quantity);
    }

    public function test_authenticated_user_can_delete_an_order_and_its_items(): void
    {
        $admin = User::factory()->create();
        $order = Order::query()->create([
            'order_number' => 'ORD00001',
            'customer_name' => 'Delete Customer',
            'customer_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
            'subtotal' => 500,
            'shipping_cost' => 50,
            'total' => 550,
            'payment_method' => 'cash_on_delivery',
            'status' => 'pending',
        ]);
        $item = $order->items()->create([
            'product_title' => 'Delete Product',
            'unit_price' => 500,
            'quantity' => 1,
            'line_total' => 500,
        ]);

        $this->actingAs($admin)->delete(route('orders.destroy', $order))
            ->assertRedirect(route('orders.index'));

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['id' => $item->id]);
    }

    public function test_authenticated_user_can_create_filter_and_export_orders(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['title' => 'Dashboard Product', 'price' => 800, 'sale_price' => null, 'stock_quantity' => 5]);

        $this->actingAs($admin)->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Orders Management')
            ->assertSee('New Order')
            ->assertSee('All Delivery Areas');

        $response = $this->actingAs($admin)->post(route('orders.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
            'customer_name' => 'Dashboard Customer',
            'customer_phone' => '01900000000',
            'customer_email' => 'dashboard@example.com',
            'shipping_address' => 'Dhaka City',
            'customer_note' => 'Admin order.',
            'delivery_area' => 'dhaka_city',
        ]);

        $order = Order::query()->firstOrFail();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame('ORD00001', $order->order_number);
        $this->assertSame('1650.00', $order->total);
        $this->assertSame(3, $product->fresh()->stock_quantity);

        $this->actingAs($admin)->get(route('orders.index', ['search' => 'Dashboard Customer', 'status' => 'pending', 'delivery_area' => 'dhaka_city']))
            ->assertOk()
            ->assertSee('ORD00001');

        $this->actingAs($admin)->get(route('orders.export', ['status' => 'pending']))
            ->assertOk()
            ->assertDownload();
    }

    public function test_new_orders_appear_in_the_notification_feed_until_viewed(): void
    {
        $admin = User::factory()->create();
        $order = Order::query()->create([
            'order_number' => 'ORD00001', 'customer_name' => 'Notification Customer', 'customer_phone' => '01700000000',
            'shipping_address' => 'Dhaka', 'subtotal' => 500, 'shipping_cost' => 50, 'total' => 550,
            'payment_method' => 'cash_on_delivery', 'status' => 'pending',
        ]);

        $this->actingAs($admin)->get(route('orders.notifications'))
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('orders.0.number', 'ORD00001')
            ->assertJsonPath('orders.0.unread', true);

        $this->actingAs($admin)->get(route('orders.show', $order))->assertOk();
        $this->actingAs($admin)->get(route('orders.notifications'))
            ->assertJsonPath('unread_count', 0)
            ->assertJsonPath('orders.0.unread', false);
    }

    public function test_repeat_customers_are_identified_by_phone_or_email(): void
    {
        $admin = User::factory()->create();
        foreach (range(1, 3) as $number) {
            Order::query()->create([
                'order_number' => 'ORD'.str_pad((string) $number, 5, '0', STR_PAD_LEFT),
                'customer_name' => 'Repeat Customer', 'customer_phone' => '01736741793',
                'customer_email' => 'repeat@example.com', 'shipping_address' => 'Dhaka',
                'subtotal' => 500, 'shipping_cost' => 50, 'total' => 550,
                'payment_method' => 'cash_on_delivery', 'status' => 'pending',
            ]);
        }

        $this->actingAs($admin)->get(route('orders.index'))
            ->assertOk()
            ->assertSee('Repeat');

        $this->actingAs($admin)->get(route('orders.show', Order::query()->firstOrFail()))
            ->assertOk()
            ->assertSee('Repeat');
    }
}
