<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FlashSaleSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_flash_sale_settings(): void
    {
        $this->get(route('settings.flash-sale.edit'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_open_flash_sale_settings(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('settings.flash-sale.edit'))
            ->assertOk()
            ->assertSee('Flash Sale')
            ->assertSee('Brand gradient')
            ->assertSee('Countdown end date and time');
    }

    public function test_admin_can_save_flash_sale_settings_that_render_on_the_storefront(): void
    {
        $endsAt = now()->addHours(5)->startOfMinute();

        $this->actingAs(User::factory()->create())->put(route('settings.flash-sale.update'), [
            'enabled' => '1',
            'include_all_sale_products' => '1',
            'eyebrow' => 'Weekend offer',
            'title' => 'Mega Flash Deal',
            'description' => 'Save more before this campaign closes.',
            'ends_at' => $endsAt->format('Y-m-d H:i:s'),
            'button_text' => 'Shop deals',
            'background_from' => '#111111',
            'background_via' => '#222222',
            'background_to' => '#333333',
        ])->assertRedirect(route('settings.flash-sale.edit'));

        $flashSale = WebsiteSetting::query()->firstOrFail()->flash_sale_settings;
        $this->assertTrue($flashSale['enabled']);
        $this->assertSame('Mega Flash Deal', $flashSale['title']);
        $this->assertSame('#222222', $flashSale['background_via']);
        $this->assertNotNull($flashSale['ends_at']);

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('Weekend offer')
            ->assertSee('Mega Flash Deal')
            ->assertSee('Save more before this campaign closes.')
            ->assertSee('Shop deals')
            ->assertSee('linear-gradient(to right, #111111, #222222, #333333)', false);

        $this->get(route('storefront.flash-sale'))
            ->assertOk()
            ->assertSee('Mega Flash Deal')
            ->assertSee('Weekend offer');
    }

    public function test_manual_mode_only_shows_selected_eligible_sale_products(): void
    {
        $selectedProduct = Product::factory()->create([
            'title' => 'Selected Flash Product',
            'status' => 'published',
            'visibility' => 'public',
            'price' => 1000,
            'sale_price' => 700,
        ]);
        Product::factory()->create([
            'title' => 'Unselected Sale Product',
            'status' => 'published',
            'visibility' => 'public',
            'price' => 1200,
            'sale_price' => 800,
        ]);

        $payload = $this->validPayload([
            'include_all_sale_products' => '0',
            'product_ids' => [$selectedProduct->id],
        ]);

        $this->actingAs(User::factory()->create())
            ->put(route('settings.flash-sale.update'), $payload)
            ->assertRedirect(route('settings.flash-sale.edit'));

        $this->assertSame([$selectedProduct->id], WebsiteSetting::query()->firstOrFail()->flash_sale_settings['product_ids']);

        $this->get(route('storefront.flash-sale'))
            ->assertOk()
            ->assertSee('Selected Flash Product')
            ->assertDontSee('Unselected Sale Product');
    }

    public function test_manual_mode_requires_at_least_one_product(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('settings.flash-sale.update'), $this->validPayload([
                'include_all_sale_products' => '0',
                'product_ids' => [],
            ]))
            ->assertSessionHasErrors('product_ids');
    }

    public function test_disabled_flash_sale_is_hidden_from_homepage(): void
    {
        WebsiteSetting::factory()->create([
            'flash_sale_settings' => array_merge(WebsiteSetting::defaultFlashSaleSettings(), ['enabled' => false]),
        ]);

        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertDontSee('aria-label="Flash sale"', false);
    }

    public function test_flash_sale_colors_must_be_valid_hex_values(): void
    {
        $this->actingAs(User::factory()->create())->put(route('settings.flash-sale.update'), [
            'enabled' => '1',
            'include_all_sale_products' => '1',
            'eyebrow' => 'Offer',
            'title' => 'Flash Sale',
            'description' => 'A limited offer.',
            'ends_at' => '',
            'button_text' => 'View all',
            'background_from' => 'red',
            'background_via' => '#222222',
            'background_to' => '#333333',
        ])->assertSessionHasErrors('background_from');
    }

    /** @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'enabled' => '1',
            'include_all_sale_products' => '1',
            'eyebrow' => 'Offer',
            'title' => 'Flash Sale',
            'description' => 'A limited offer.',
            'ends_at' => '',
            'button_text' => 'View all',
            'background_from' => '#111111',
            'background_via' => '#222222',
            'background_to' => '#333333',
        ], $overrides);
    }
}
