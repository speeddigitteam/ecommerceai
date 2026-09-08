<?php

namespace Tests\Feature;

use App\Models\CourierIntegration;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_only_follow_valid_status_transitions(): void
    {
        $admin = User::factory()->create();
        $order = $this->order();

        $this->actingAs($admin)->patch(route('orders.status.update', $order), [
            'status' => OrderStatus::Processing->value,
        ])->assertSessionHasErrors('status');

        $this->actingAs($admin)->patch(route('orders.status.update', $order), [
            'status' => OrderStatus::Confirmed->value,
        ])->assertRedirect(route('orders.show', $order));

        $this->assertSame(OrderStatus::Confirmed->value, $order->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::Pending->value,
            'to_status' => OrderStatus::Confirmed->value,
            'user_id' => $admin->id,
        ]);
    }

    public function test_cancelling_an_order_restores_stock_once(): void
    {
        $admin = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 3]);
        $order = $this->order($product, 2);

        $this->actingAs($admin)->patch(route('orders.status.update', $order), [
            'status' => OrderStatus::Cancelled->value,
        ])->assertRedirect(route('orders.show', $order));

        $this->assertSame(5, $product->fresh()->stock_quantity);
        $this->assertNotNull($order->fresh()->stock_restored_at);
        $this->actingAs($admin)->patch(route('orders.status.update', $order), [
            'status' => OrderStatus::Cancelled->value,
        ])->assertSessionHasErrors('status');
        $this->assertSame(5, $product->fresh()->stock_quantity);
    }

    public function test_ready_order_can_be_sent_to_steadfast_only_once(): void
    {
        Http::fake([
            'https://portal.packzy.com/api/v1/create_order' => Http::response([
                'status' => 200,
                'consignment' => ['consignment_id' => 12345, 'tracking_code' => 'TRACK-123'],
            ]),
        ]);
        $admin = User::factory()->create();
        CourierIntegration::factory()->create();
        $order = $this->order(status: OrderStatus::ReadyToShip->value);

        $this->actingAs($admin)->post(route('orders.courier.send', $order))
            ->assertRedirect(route('orders.show', $order));

        $this->assertDatabaseHas('order_shipments', [
            'order_id' => $order->id,
            'external_id' => '12345',
            'tracking_code' => 'TRACK-123',
            'status' => 'submitted',
        ]);
        $this->actingAs($admin)->post(route('orders.courier.send', $order))->assertStatus(422);
        Http::assertSentCount(1);
    }

    public function test_delivered_courier_status_completes_order_with_history(): void
    {
        Http::fake([
            'https://portal.packzy.com/api/v1/status_by_cid/12345' => Http::response([
                'status' => 200,
                'delivery_status' => 'delivered',
            ]),
        ]);
        $admin = User::factory()->create();
        $integration = CourierIntegration::factory()->create();
        $order = $this->order(status: OrderStatus::ReadyToShip->value);
        $order->shipment()->create([
            'courier_integration_id' => $integration->id,
            'provider' => 'steadfast',
            'external_id' => '12345',
            'status' => 'submitted',
            'sent_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('orders.courier.sync', $order))
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(OrderStatus::Completed->value, $order->fresh()->status);
        $this->assertNotNull($order->shipment->fresh()->delivered_at);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'to_status' => OrderStatus::Shipped->value]);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'to_status' => OrderStatus::Completed->value]);
    }

    public function test_courier_confirmed_cancellation_cancels_order_and_restores_stock(): void
    {
        Http::fake([
            'https://portal.packzy.com/api/v1/status_by_cid/12345' => Http::response([
                'status' => 200,
                'delivery_status' => 'cancelled',
            ]),
        ]);
        $admin = User::factory()->create();
        $integration = CourierIntegration::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 3]);
        $order = $this->order($product, 2, OrderStatus::ReadyToShip->value);
        $order->shipment()->create([
            'courier_integration_id' => $integration->id,
            'provider' => 'steadfast',
            'external_id' => '12345',
            'status' => 'submitted',
            'sent_at' => now(),
        ]);

        $this->actingAs($admin)->post(route('orders.courier.sync', $order))
            ->assertRedirect(route('orders.show', $order));

        $this->assertSame(OrderStatus::Cancelled->value, $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->id,
            'to_status' => OrderStatus::Cancelled->value,
            'note' => 'Courier confirmed parcel cancellation.',
        ]);
    }

    private function order(?Product $product = null, int $quantity = 1, string $status = OrderStatus::Pending->value): Order
    {
        $product ??= Product::factory()->create(['stock_quantity' => 10]);
        $order = Order::query()->create([
            'order_number' => 'ORD'.fake()->unique()->numerify('#####'),
            'customer_name' => 'Workflow Customer',
            'customer_phone' => '01700000000',
            'shipping_address' => 'Dhaka',
            'subtotal' => 100,
            'shipping_cost' => 50,
            'total' => 150,
            'payment_method' => 'cash_on_delivery',
            'status' => $status,
        ]);
        $order->items()->create([
            'product_id' => $product->id,
            'product_title' => $product->title,
            'sku' => $product->sku,
            'unit_price' => 100,
            'quantity' => $quantity,
            'line_total' => 100 * $quantity,
        ]);

        return $order;
    }
}
