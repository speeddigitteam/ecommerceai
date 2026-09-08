<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_user_management(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_only_admin_panel_users(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create(['name' => 'Jane Doe']);
        $customer = User::factory()->customer()->create(['name' => 'Customer Account']);

        $this->actingAs($admin)->get(route('users.index'))
            ->assertOk()
            ->assertSee('Admin users')
            ->assertSee($user->name)
            ->assertSee($user->email)
            ->assertDontSee($customer->name)
            ->assertDontSee($customer->email);
    }

    public function test_authenticated_user_can_create_a_user(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'customer',
            'profile_image' => UploadedFile::fake()->image('profile.jpg', 300, 300),
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'new@example.com',
            'role' => UserRole::Admin->value,
        ]);
        $user = User::query()->where('email', 'new@example.com')->firstOrFail();
        Storage::disk('public')->assertExists($user->profile_image_path);
    }

    public function test_new_user_requires_unique_email_and_confirmed_password(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Duplicate User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'different-password',
            'role' => 'customer',
        ])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_authenticated_user_can_view_and_update_a_user(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $user = User::factory()->create(['name' => 'Original Name']);

        $this->actingAs($admin)->get(route('users.show', $user))->assertOk()->assertSee('Original Name');
        $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
            'role' => 'customer',
            'profile_image' => UploadedFile::fake()->image('updated-profile.png', 300, 300),
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name']);
        $user->refresh();
        $this->assertSame(UserRole::Admin, $user->role);
        Storage::disk('public')->assertExists($user->profile_image_path);
    }

    public function test_customers_cannot_be_managed_through_admin_user_routes(): void
    {
        $admin = User::factory()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($admin)->get(route('users.show', $customer))->assertNotFound();
        $this->actingAs($admin)->get(route('users.edit', $customer))->assertNotFound();
        $this->actingAs($admin)->put(route('users.update', $customer), [
            'name' => $customer->name,
            'email' => $customer->email,
            'password' => '',
            'password_confirmation' => '',
        ])->assertNotFound();
        $this->actingAs($admin)->delete(route('users.destroy', $customer))->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $customer->id]);
    }

    public function test_authenticated_user_can_delete_another_user_but_not_themselves(): void
    {
        $admin = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)->delete(route('users.destroy', $user))->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        $this->actingAs($admin)->delete(route('users.destroy', $admin))->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
