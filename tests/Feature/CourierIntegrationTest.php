<?php

namespace Tests\Feature;

use App\Models\CourierIntegration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CourierIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_courier_integrations(): void
    {
        $this->get(route('courier-integrations.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_view_courier_integration_table(): void
    {
        $admin = User::factory()->create();
        $integration = CourierIntegration::factory()->create();

        $this->actingAs($admin)
            ->get(route('courier-integrations.index'))
            ->assertOk()
            ->assertSee('Courier Integration')
            ->assertSee('Add New Courier')
            ->assertSee('images/couriers/steadfast.jpg')
            ->assertSee('images/couriers/pathao.png')
            ->assertSee('images/couriers/redx.jpg')
            ->assertSee('images/couriers/paperfly.png')
            ->assertSee($integration->name);
    }

    public function test_admin_can_add_steadfast_with_encrypted_credentials(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('courier-integrations.store'), [
            'provider' => 'steadfast',
            'name' => 'Main Steadfast',
            'api_key' => 'live-api-key',
            'secret_key' => 'live-secret-key',
            'base_url' => 'https://portal.packzy.com/api/v1',
            'is_active' => '1',
        ])->assertRedirect(route('courier-integrations.index'));

        $integration = CourierIntegration::query()->firstOrFail();

        $this->assertSame('live-api-key', $integration->api_key);
        $this->assertSame('live-secret-key', $integration->secret_key);
        $this->assertNotSame('live-api-key', $integration->getRawOriginal('api_key'));
        $this->assertNotSame('live-secret-key', $integration->getRawOriginal('secret_key'));
    }

    public function test_admin_can_add_each_supported_courier_provider(): void
    {
        $admin = User::factory()->create();

        foreach (['pathao', 'redx', 'paperfly'] as $provider) {
            $this->actingAs($admin)->post(route('courier-integrations.store'), [
                'provider' => $provider,
                'name' => ucfirst($provider).' Courier',
                'api_key' => $provider.'-api-key',
                'secret_key' => $provider.'-secret-key',
                'base_url' => 'https://api.example.com/'.$provider,
                'is_active' => '1',
            ])->assertRedirect(route('courier-integrations.index'));
        }

        $this->assertDatabaseCount('courier_integrations', 3);
    }

    public function test_admin_can_test_steadfast_connection_and_toggle_it(): void
    {
        Http::fake([
            'https://portal.packzy.com/api/v1/get_balance' => Http::response([
                'status' => 200,
                'current_balance' => 1250.50,
            ]),
        ]);
        $admin = User::factory()->create();
        $integration = CourierIntegration::factory()->create();

        $this->actingAs($admin)
            ->post(route('courier-integrations.test', $integration))
            ->assertRedirect(route('courier-integrations.index'))
            ->assertSessionHas('status');

        $integration->refresh();
        $this->assertTrue($integration->last_test_succeeded);
        $this->assertNotNull($integration->last_tested_at);

        $this->actingAs($admin)
            ->patch(route('courier-integrations.toggle', $integration))
            ->assertRedirect(route('courier-integrations.index'));

        $this->assertFalse($integration->fresh()->is_active);
    }
}
