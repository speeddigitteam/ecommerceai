<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_screen_can_be_rendered(): void
    {
        $this->get(route('admin.login'))->assertOk()->assertSee('Admin login');
    }

    public function test_admin_can_login_from_admin_portal(): void
    {
        $admin = User::factory()->create();

        $response = $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_customer_cannot_login_from_admin_portal(): void
    {
        $customer = User::factory()->customer()->create();

        $this->post(route('admin.login.store'), ['email' => $customer->email, 'password' => 'password']);

        $this->assertGuest();
    }

    public function test_admin_cannot_login_from_customer_portal(): void
    {
        $admin = User::factory()->create();

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'password']);

        $this->assertGuest();
    }

    public function test_customer_cannot_access_admin_dashboard(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get(route('dashboard'))->assertForbidden();
    }

    public function test_guest_is_sent_to_admin_login_from_admin_dashboard(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('admin.login'));
    }
}
