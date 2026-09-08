<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_page_is_publicly_available(): void
    {
        $this->get(route('storefront.orders.track'))
            ->assertOk()
            ->assertSee('Where is your order?');
    }

    public function test_customer_can_track_an_order_with_matching_number_and_phone(): void
    {
        $order = $this->createOrder();
        $order->items()->create([
            'product_title' => 'Tracking Test Product',
            'sku' => 'TRACK-1',
            'unit_price' => 500,
            'quantity' => 2,
            'line_total' => 1000,
        ]);
        $order->statusHistories()->create([
            'from_status' => 'pending',
            'to_status' => 'confirmed',
            'note' => 'Private admin note.',
        ]);

        $this->post(route('storefront.orders.track.show'), [
            'order_number' => strtolower($order->order_number),
            'customer_phone' => $order->customer_phone,
        ])
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Tracking Test Product')
            ->assertSee('Confirmed')
            ->assertDontSee('Private admin note.');
    }

    public function test_tracking_does_not_reveal_an_order_when_phone_does_not_match(): void
    {
        $order = $this->createOrder();

        $this->post(route('storefront.orders.track.show'), [
            'order_number' => $order->order_number,
            'customer_phone' => '01999999999',
        ])
            ->assertOk()
            ->assertSee('Order not found');
    }

    public function test_tracking_requires_a_valid_order_number_and_phone(): void
    {
        $this->post(route('storefront.orders.track.show'), [
            'order_number' => 'invalid',
            'customer_phone' => '',
        ])->assertSessionHasErrors(['order_number', 'customer_phone']);
    }

    private function createOrder(): Order
    {
        return Order::query()->create([
            'order_number' => 'ORD00008',
            'customer_name' => 'Tracking Customer',
            'customer_phone' => '01700000000',
            'customer_email' => 'tracking@example.com',
            'shipping_address' => 'Uttara, Dhaka',
            'subtotal' => 1000,
            'shipping_cost' => 50,
            'total' => 1050,
            'payment_method' => 'cash_on_delivery',
            'status' => 'confirmed',
        ]);
    }
}
