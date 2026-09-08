<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_analytics_settings(): void
    {
        $this->get(route('settings.analytics.edit'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_open_analytics_settings_page(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('settings.analytics.edit'))
            ->assertOk()
            ->assertSee('Google Integrations')
            ->assertSee('Google Search Console')
            ->assertSee('Save Google settings');
    }

    public function test_admin_can_enable_tracking_with_a_measurement_id(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('settings.analytics.update'), [
            'tracking_enabled' => '1',
            'measurement_id' => 'G-ABC1234567',
        ])->assertRedirect(route('settings.analytics.edit'));

        $settings = WebsiteSetting::query()->firstOrFail();
        $this->assertTrue($settings->ga_tracking_enabled);
        $this->assertSame('G-ABC1234567', $settings->ga_measurement_id);
    }

    public function test_measurement_id_must_match_the_expected_format(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('settings.analytics.update'), [
            'tracking_enabled' => '1',
            'measurement_id' => 'not-a-valid-id',
        ])->assertSessionHasErrors(['measurement_id']);
    }

    public function test_admin_can_save_a_service_account_json_key_and_it_is_encrypted(): void
    {
        $admin = User::factory()->create();
        $json = json_encode(['client_email' => 'svc@example.iam.gserviceaccount.com', 'private_key' => 'fake-key']);

        $this->actingAs($admin)->put(route('settings.analytics.update'), [
            'tracking_enabled' => '0',
            'property_id' => '123456789',
            'service_account_json' => $json,
        ])->assertRedirect(route('settings.analytics.edit'));

        $settings = WebsiteSetting::query()->firstOrFail();
        $this->assertSame('123456789', $settings->ga_property_id);
        $this->assertSame($json, $settings->ga_service_account_json);
        $this->assertNotSame($json, DB::table('website_settings')->value('ga_service_account_json'));
    }

    public function test_invalid_service_account_json_is_rejected(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('settings.analytics.update'), [
            'tracking_enabled' => '0',
            'service_account_json' => '{"foo":"bar"}',
        ])->assertSessionHasErrors(['service_account_json']);
    }

    public function test_admin_can_save_a_search_console_verification_code(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('settings.analytics.update'), [
            'tracking_enabled' => '0',
            'search_console_verification' => 'abc123verificationcode',
        ])->assertRedirect(route('settings.analytics.edit'));

        $settings = WebsiteSetting::query()->firstOrFail();
        $this->assertSame('abc123verificationcode', $settings->search_console_verification);
    }

    public function test_search_console_verification_meta_tag_is_rendered_on_the_storefront(): void
    {
        WebsiteSetting::factory()->create(['search_console_verification' => 'abc123verificationcode']);

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('<meta name="google-site-verification" content="abc123verificationcode">', false);
    }
}
