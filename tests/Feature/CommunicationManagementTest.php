<?php

namespace Tests\Feature;

use App\Models\CommunicationProvider;
use App\Models\MessageTemplate;
use App\Models\Notice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CommunicationManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_an_encrypted_email_provider(): void
    {
        $admin = User::factory()->create();
        $payload = ['name' => 'Primary SMTP', 'driver' => 'smtp', 'host' => 'smtp.example.com', 'port' => 587, 'encryption' => 'tls', 'username' => 'user', 'password' => 'secret-value', 'from_address' => 'store@example.com', 'from_name' => 'Store'];
        $this->actingAs($admin)->post(route('communication.providers.store', 'email'), $payload)->assertRedirect();
        $provider = CommunicationProvider::query()->firstOrFail();

        $this->assertSame('secret-value', $provider->settings['password']);
        $this->assertStringNotContainsString('secret-value', $provider->getRawOriginal('settings'));
        $this->actingAs($admin)->get(route('communication.providers.index', 'email'))->assertOk()->assertSee('Primary SMTP')->assertSee(':disabled="!editing"', false);
        $this->actingAs($admin)->patch(route('communication.providers.toggle', ['email', $provider]))->assertSessionHasNoErrors();
        $this->assertTrue($provider->fresh()->is_active);
    }

    public function test_admin_can_create_templates_notices_and_send_sms(): void
    {
        Http::fake(['https://sms.example.com/*' => Http::response(['success' => true])]);
        $admin = User::factory()->create();
        $provider = CommunicationProvider::factory()->create(['channel' => 'sms', 'driver' => 'http', 'is_active' => true, 'settings' => ['url' => 'https://sms.example.com/send', 'api_key' => 'secret', 'sender_id' => 'Store', 'to_parameter' => 'to', 'message_parameter' => 'message', 'api_key_parameter' => 'api_key']]);

        $this->actingAs($admin)->post(route('communication.templates.store', 'sms'), ['name' => 'Welcome', 'body' => 'Welcome'])->assertRedirect();
        $this->actingAs($admin)->post(route('communication.notices.store'), ['name' => 'Maintenance', 'details' => 'Tonight', 'publish_to' => ['dashboard'], 'is_active' => 1])->assertRedirect();
        $this->actingAs($admin)->post(route('communication.manual.send', 'sms'), ['group' => 'none', 'recipients' => '01712345678', 'body' => 'Order ready'])->assertSessionHas('status');

        $this->assertDatabaseHas(MessageTemplate::class, ['channel' => 'sms', 'name' => 'Welcome']);
        $this->assertDatabaseHas(Notice::class, ['name' => 'Maintenance']);
        $this->assertDatabaseHas('communication_logs', ['communication_provider_id' => $provider->id, 'status' => 'sent']);
        Http::assertSent(fn ($request): bool => $request['api_key'] === 'secret');
    }

    public function test_customer_cannot_access_communication_management(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get(route('communication.providers.index', 'email'))->assertForbidden();
    }
}
