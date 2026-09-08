<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_customer_can_view_their_dashboard_and_orders(): void
    {
        $customer = User::factory()->customer()->create(['email' => 'customer@example.com']);
        Order::query()->create($this->orderData(['customer_email' => $customer->email, 'order_number' => 'ORD00001']));
        Order::query()->create($this->orderData(['customer_email' => 'other@example.com', 'order_number' => 'ORD00002']));

        $this->actingAs($customer)->get(route('customer.dashboard'))
            ->assertOk()->assertSee('Your account overview')->assertSee('ORD00001')->assertDontSee('ORD00002');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('customer.dashboard'))->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_customer_dashboard(): void
    {
        $this->actingAs(User::factory()->create())->get(route('customer.dashboard'))->assertForbidden();
    }

    public function test_customer_can_open_the_change_password_page(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get(route('customer.password.edit'))
            ->assertOk()
            ->assertSee('Change your password')
            ->assertSee('Current password');
    }

    public function test_admin_cannot_access_customer_change_password_page(): void
    {
        $this->actingAs(User::factory()->create())->get(route('customer.password.edit'))->assertForbidden();
    }

    /** @param array<string, mixed> $overrides */
    private function orderData(array $overrides = []): array
    {
        return array_merge([
            'order_number' => 'ORD00001', 'customer_name' => 'Customer', 'customer_phone' => '01700000000',
            'customer_email' => 'customer@example.com', 'shipping_address' => 'Dhaka', 'subtotal' => 500,
            'shipping_cost' => 50, 'total' => 550, 'payment_method' => 'cash_on_delivery', 'status' => 'pending',
        ], $overrides);
    }
}
