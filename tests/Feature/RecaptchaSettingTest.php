<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecaptchaSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_recaptcha_settings(): void
    {
        $this->get(route('settings.recaptcha.edit'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_open_recaptcha_settings_from_the_website_settings_submenu(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('settings.recaptcha.edit'))
            ->assertOk()
            ->assertSee('Google reCAPTCHA')
            ->assertSee('Save reCAPTCHA settings')
            ->assertSee('reCAPTCHA');
    }

    public function test_admin_can_enable_recaptcha_and_the_secret_is_encrypted(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('settings.recaptcha.update'), [
            'enabled' => '1',
            'site_key' => 'public-site-key',
            'secret_key' => 'private-secret-key',
        ])->assertRedirect(route('settings.recaptcha.edit'));

        $settings = WebsiteSetting::query()->firstOrFail();
        $this->assertTrue($settings->recaptcha_enabled);
        $this->assertSame('public-site-key', $settings->recaptcha_site_key);
        $this->assertSame('private-secret-key', $settings->recaptcha_secret_key);
        $this->assertNotSame('private-secret-key', DB::table('website_settings')->value('recaptcha_secret_key'));
    }

    public function test_recaptcha_cannot_be_enabled_without_both_keys(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->put(route('settings.recaptcha.update'), [
            'enabled' => '1',
            'site_key' => '',
            'secret_key' => '',
        ])->assertSessionHasErrors(['site_key', 'secret_key']);
    }

    public function test_enabled_recaptcha_is_rendered_on_customer_and_admin_login_forms(): void
    {
        WebsiteSetting::factory()->create([
            'recaptcha_enabled' => true,
            'recaptcha_site_key' => 'public-site-key',
            'recaptcha_secret_key' => 'private-secret-key',
        ]);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('https://www.google.com/recaptcha/api.js', false)
            ->assertSee('data-sitekey="public-site-key"', false);

        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('https://www.google.com/recaptcha/api.js', false)
            ->assertSee('data-sitekey="public-site-key"', false);
    }

    public function test_customer_login_is_blocked_without_a_valid_recaptcha_token(): void
    {
        WebsiteSetting::factory()->create([
            'recaptcha_enabled' => true,
            'recaptcha_site_key' => 'public-site-key',
            'recaptcha_secret_key' => 'private-secret-key',
        ]);
        $customer = User::factory()->customer()->create();
        Http::fake();

        $this->post(route('login'), [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertSessionHasErrors('g-recaptcha-response');

        $this->assertGuest();
        Http::assertNothingSent();
    }

    public function test_customer_and_admin_can_login_after_google_verifies_the_token(): void
    {
        WebsiteSetting::factory()->create([
            'recaptcha_enabled' => true,
            'recaptcha_site_key' => 'public-site-key',
            'recaptcha_secret_key' => 'private-secret-key',
        ]);
        Http::fake(['www.google.com/recaptcha/api/siteverify' => Http::response(['success' => true])]);
        $customer = User::factory()->customer()->create();

        $this->post(route('login'), [
            'email' => $customer->email,
            'password' => 'password',
            'g-recaptcha-response' => 'customer-token',
        ])->assertRedirect(route('customer.dashboard', absolute: false));
        $this->assertAuthenticatedAs($customer);

        $this->post(route('logout'));
        $admin = User::factory()->create();

        $this->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
            'g-recaptcha-response' => 'admin-token',
        ])->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($admin);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://www.google.com/recaptcha/api/siteverify'
            && $request['secret'] === 'private-secret-key'
            && in_array($request['response'], ['customer-token', 'admin-token'], true));
    }
}
