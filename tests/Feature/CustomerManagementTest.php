<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_customer_list(): void
    {
        $this->get(route('customers.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_only_customer_accounts(): void
    {
        $admin = User::factory()->create([
            'name' => 'Store Admin',
            'role' => UserRole::Admin,
        ]);
        $customer = User::factory()->customer()->create(['name' => 'Jane Customer']);

        $this->actingAs($admin)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Customer list')
            ->assertSee($customer->name)
            ->assertSee($customer->email)
            ->assertViewHas('customers', fn ($customers): bool => $customers->total() === 1
                && $customers->first()->is($customer));
    }

    public function test_customer_cannot_access_admin_customer_list(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('customers.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_a_verified_customer_with_a_password(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->post(route('customers.store'), [
            'name' => 'Created Customer',
            'email' => 'created@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertRedirect(route('customers.index'));

        $customer = User::query()->where('email', 'created@example.com')->firstOrFail();

        $this->assertSame(UserRole::Customer, $customer->role);
        $this->assertNotNull($customer->email_verified_at);
        $this->assertTrue(Hash::check('SecurePass123!', $customer->password));
    }

    public function test_customer_creation_requires_unique_email_and_confirmed_password(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        User::factory()->customer()->create(['email' => 'existing@example.com']);

        $this->actingAs($admin)->post(route('customers.store'), [
            'name' => 'Another Customer',
            'email' => 'existing@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_admin_can_view_update_and_delete_a_customer_without_purchases(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $customer = User::factory()->customer()->create();

        $this->actingAs($admin)->get(route('customers.show', $customer))->assertOk()->assertSee($customer->name);
        $this->actingAs($admin)->put(route('customers.update', $customer), [
            'name' => 'Updated Customer',
            'email' => 'updated-customer@example.com',
        ])->assertRedirect(route('customers.index'));
        $this->actingAs($admin)->delete(route('customers.destroy', $customer))->assertRedirect(route('customers.index'));

        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
    }

    public function test_customer_with_linked_or_legacy_purchase_cannot_be_deleted(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $linkedCustomer = User::factory()->customer()->create();
        $legacyCustomer = User::factory()->customer()->create(['email' => 'legacy@example.com']);
        Order::query()->create($this->orderData(['user_id' => $linkedCustomer->id, 'customer_email' => $linkedCustomer->email, 'order_number' => 'ORD00001']));
        Order::query()->create($this->orderData(['customer_email' => $legacyCustomer->email, 'order_number' => 'ORD00002']));

        $this->actingAs($admin)->delete(route('customers.destroy', $linkedCustomer))->assertSessionHas('error');
        $this->actingAs($admin)->delete(route('customers.destroy', $legacyCustomer))->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $linkedCustomer->id]);
        $this->assertDatabaseHas('users', ['id' => $legacyCustomer->id]);
    }

    /** @param array<string, mixed> $overrides */
    private function orderData(array $overrides = []): array
    {
        return array_merge([
            'user_id' => null, 'order_number' => 'ORD00001', 'customer_name' => 'Customer',
            'customer_phone' => '01700000000', 'customer_email' => null, 'shipping_address' => 'Dhaka',
            'subtotal' => 500, 'shipping_cost' => 50, 'total' => 550, 'payment_method' => 'cash_on_delivery',
            'status' => 'pending',
        ], $overrides);
    }
}
