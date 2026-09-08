<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HeroSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_hero_settings(): void
    {
        $this->get(route('settings.hero.edit'))->assertRedirect(route('admin.login'));
    }

    public function test_authenticated_user_can_select_featured_product_and_upload_slider_images(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $product = Product::factory()->create(['title' => 'Selected Hero Product']);

        $this->actingAs($user)->put(route('settings.hero.update'), [
            'hero_title' => 'Summer collection',
            'hero_subtitle' => 'Made for brighter days',
            'hero_product_id' => $product->id,
            'slider_images' => [
                UploadedFile::fake()->image('first.jpg', 1200, 900),
                UploadedFile::fake()->image('second.webp', 1200, 900),
            ],
        ])->assertRedirect(route('settings.hero.edit'));

        $settings = WebsiteSetting::query()->firstOrFail();

        $this->assertSame('Summer collection', $settings->hero_title);
        $this->assertSame($product->id, $settings->hero_product_id);
        $this->assertCount(2, $settings->hero_slider_paths);
        Storage::disk('public')->assertExists($settings->hero_slider_paths[0]);
        $this->get(route('storefront.index'))->assertOk()->assertSee('Hero slide');
    }

    public function test_existing_slider_image_can_be_removed(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('website/hero/old.jpg', 'image');
        $settings = WebsiteSetting::factory()->create(['hero_slider_paths' => ['website/hero/old.jpg']]);

        $this->actingAs(User::factory()->create())->put(route('settings.hero.update'), [
            'remove_slider_images' => ['website/hero/old.jpg'],
        ])->assertSessionHasNoErrors();

        $this->assertSame([], $settings->fresh()->hero_slider_paths);
        Storage::disk('public')->assertMissing('website/hero/old.jpg');
    }

    public function test_two_side_images_can_be_uploaded_and_displayed_on_the_storefront(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())->put(route('settings.hero.update'), [
            'hero_side_image_one' => UploadedFile::fake()->image('side-one.jpg', 600, 400),
            'hero_side_image_two' => UploadedFile::fake()->image('side-two.webp', 600, 400),
        ])->assertRedirect(route('settings.hero.edit'));

        $settings = WebsiteSetting::query()->firstOrFail();

        $this->assertNotNull($settings->hero_side_image_one_path);
        $this->assertNotNull($settings->hero_side_image_two_path);
        Storage::disk('public')->assertExists($settings->hero_side_image_one_path);
        Storage::disk('public')->assertExists($settings->hero_side_image_two_path);
        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('Hero side image 1')
            ->assertSee('Hero side image 2');
    }

    public function test_existing_side_image_can_be_replaced(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('website/hero/side/old.jpg', 'image');
        $settings = WebsiteSetting::factory()->create([
            'hero_side_image_one_path' => 'website/hero/side/old.jpg',
        ]);

        $this->actingAs(User::factory()->create())->put(route('settings.hero.update'), [
            'hero_side_image_one' => UploadedFile::fake()->image('replacement.jpg', 600, 400),
        ])->assertSessionHasNoErrors();

        $updatedPath = $settings->fresh()->hero_side_image_one_path;

        $this->assertNotSame('website/hero/side/old.jpg', $updatedPath);
        Storage::disk('public')->assertMissing('website/hero/side/old.jpg');
        Storage::disk('public')->assertExists($updatedPath);
    }
}
