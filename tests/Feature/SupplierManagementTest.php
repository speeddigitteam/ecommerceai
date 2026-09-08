<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_suppliers(): void
    {
        $this->get(route('suppliers.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_supplier_list(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $supplier = Supplier::factory()->create();

        $this->actingAs($admin)->get(route('suppliers.index'))->assertOk()->assertSee('Supplier list')->assertSee($supplier->name);
    }

    public function test_admin_can_create_update_and_delete_supplier(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->post(route('suppliers.store'), ['name' => 'Dhaka Wholesale', 'phone' => '01700111222', 'business_address' => 'Islampur, Dhaka'])->assertRedirect(route('suppliers.index'));
        $supplier = Supplier::query()->firstOrFail();
        $this->actingAs($admin)->put(route('suppliers.update', $supplier), ['name' => 'Dhaka Wholesale Ltd', 'phone' => '01700111222', 'business_address' => 'Chawkbazar, Dhaka'])->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['name' => 'Dhaka Wholesale Ltd']);
        $this->actingAs($admin)->delete(route('suppliers.destroy', $supplier))->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_supplier_phone_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Supplier::factory()->create(['phone' => '01700111222']);

        $this->actingAs($admin)->post(route('suppliers.store'), ['name' => 'Duplicate', 'phone' => '01700111222', 'business_address' => 'Dhaka'])->assertSessionHasErrors('phone');
    }
}
