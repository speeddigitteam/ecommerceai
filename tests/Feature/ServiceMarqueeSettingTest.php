<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceMarqueeSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_service_marquee_settings(): void
    {
        $this->get(route('settings.service-marquee.edit'))->assertRedirect(route('admin.login'));
    }

    public function test_authenticated_user_can_update_service_marquee(): void
    {
        $items = [
            ['label' => 'Lowest prices', 'icon' => 'price'],
            ['label' => 'Free delivery', 'icon' => 'delivery'],
            ['label' => 'Premium products', 'icon' => 'quality'],
            ['label' => 'Secure payment', 'icon' => 'payment'],
            ['label' => 'Always available support', 'icon' => 'support'],
        ];

        $this->actingAs(User::factory()->create())->put(route('settings.service-marquee.update'), [
            'items' => $items,
            'speed' => 35,
            'enabled' => true,
        ])->assertRedirect(route('settings.service-marquee.edit'));

        $settings = WebsiteSetting::query()->firstOrFail();

        $this->assertSame($items, $settings->service_marquee_items);
        $this->assertSame(35, $settings->service_marquee_speed);
        $this->assertTrue($settings->service_marquee_enabled);

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('Lowest prices')
            ->assertSee('animation-duration: 35s', false);
    }

    public function test_service_marquee_can_be_disabled(): void
    {
        WebsiteSetting::factory()->create([
            'service_marquee_items' => [
                ['label' => 'One', 'icon' => 'price'],
                ['label' => 'Two', 'icon' => 'delivery'],
                ['label' => 'Three', 'icon' => 'quality'],
                ['label' => 'Four', 'icon' => 'payment'],
                ['label' => 'Five', 'icon' => 'support'],
            ],
            'service_marquee_enabled' => true,
        ]);

        $this->actingAs(User::factory()->create())->put(route('settings.service-marquee.update'), [
            'items' => [
                ['label' => 'One', 'icon' => 'price'],
                ['label' => 'Two', 'icon' => 'delivery'],
                ['label' => 'Three', 'icon' => 'quality'],
                ['label' => 'Four', 'icon' => 'payment'],
                ['label' => 'Five', 'icon' => 'support'],
            ],
            'speed' => 28,
            'enabled' => false,
        ])->assertSessionHasNoErrors();

        $this->get(route('storefront.index'))->assertOk()->assertDontSee('Service highlights');
    }
}
