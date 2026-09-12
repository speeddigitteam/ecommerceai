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
        $provider = CommunicationProvider::factory()->create(['channel' => 'sms', 'driver' => 'http', 'is_active' => true, 'settings' => ['url' => 'https://sms.example.com/send', 'api_key' => 'secret', 'sender_id' => 'Store', 'to_parameter' => 'contacts', 'message_parameter' => 'msg', 'api_key_parameter' => 'api_key', 'sender_parameter' => 'senderid', 'message_type' => 'auto', 'label' => 'transactional', 'balance_url' => 'https://sms.example.com/misc/{api_key}/balance']]);

        $this->actingAs($admin)->post(route('communication.templates.store', 'sms'), ['name' => 'Welcome', 'body' => 'Welcome'])->assertRedirect();
        $this->actingAs($admin)->post(route('communication.notices.store'), ['name' => 'Maintenance', 'details' => 'Tonight', 'publish_to' => ['dashboard'], 'is_active' => 1])->assertRedirect();
        $this->actingAs($admin)->post(route('communication.manual.send', 'sms'), ['group' => 'none', 'recipients' => '01712345678', 'body' => 'Order ready'])->assertSessionHas('status');

        $this->assertDatabaseHas(MessageTemplate::class, ['channel' => 'sms', 'name' => 'Welcome']);
        $this->assertDatabaseHas(Notice::class, ['name' => 'Maintenance']);
        $this->assertDatabaseHas('communication_logs', ['communication_provider_id' => $provider->id, 'status' => 'sent']);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://sms.example.com/send'
            && $request['api_key'] === 'secret'
            && $request['contacts'] === '01712345678'
            && $request['msg'] === 'Order ready'
            && $request['senderid'] === 'Store'
            && $request['type'] === 'text'
            && $request['label'] === 'transactional');

        $this->actingAs($admin)
            ->post(route('communication.providers.balance', ['sms', $provider]))
            ->assertSessionHas('status', 'SMS balance: {"success":true}');

        Http::assertSent(fn ($request): bool => $request->url() === 'https://sms.example.com/misc/secret/balance');
    }

    public function test_customer_cannot_access_communication_management(): void
    {
        $this->actingAs(User::factory()->customer()->create())->get(route('communication.providers.index', 'email'))->assertForbidden();
    }

    public function test_new_email_provider_requires_authentication_credentials(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('communication.providers.store', 'email'), [
                'name' => 'Incomplete SMTP',
                'driver' => 'smtp',
                'host' => 'smtp.gmail.com',
                'port' => 587,
                'encryption' => 'tls',
                'from_address' => 'store@example.com',
                'from_name' => 'Store',
            ])
            ->assertInvalid(['username', 'password']);
    }

    public function test_mram_balance_url_accepts_the_encrypted_api_key_placeholder(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('communication.providers.store', 'sms'), [
                'name' => 'MRAM SMS',
                'driver' => 'http',
                'url' => 'https://msg.mram.com.bd/smsapi',
                'api_key' => 'test-secret',
                'sender_id' => 'Store',
                'to_parameter' => 'contacts',
                'message_parameter' => 'msg',
                'api_key_parameter' => 'api_key',
                'sender_parameter' => 'senderid',
                'message_type' => 'auto',
                'label' => 'transactional',
                'balance_url' => 'https://msg.mram.com.bd/miscapi/{api_key}/getBalance',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            'https://msg.mram.com.bd/miscapi/{api_key}/getBalance',
            CommunicationProvider::query()->firstOrFail()->settings['balance_url'],
        );
    }

    public function test_sms_connection_test_displays_the_provider_error_reason(): void
    {
        Http::fake(['https://msg.mram.com.bd/*' => Http::response('1002')]);
        $admin = User::factory()->create();
        $provider = CommunicationProvider::factory()->create([
            'channel' => 'sms',
            'driver' => 'http',
            'settings' => [
                'url' => 'https://msg.mram.com.bd/smsapi',
                'api_key' => 'secret',
                'sender_id' => 'Unapproved',
                'to_parameter' => 'contacts',
                'message_parameter' => 'msg',
                'api_key_parameter' => 'api_key',
                'sender_parameter' => 'senderid',
                'message_type' => 'auto',
                'label' => 'transactional',
            ],
        ]);

        $this->actingAs($admin)
            ->post(route('communication.providers.test', ['sms', $provider]), ['test_recipient' => '01712345678'])
            ->assertSessionHas('error', 'Connection test failed: Sender ID or masking was not found.');
    }
}
