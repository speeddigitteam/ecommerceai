<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\User;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebsiteSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_website_settings(): void
    {
        $this->get(route('settings.website.edit'))->assertRedirect(route('admin.login'));
    }

    public function test_authenticated_user_can_view_website_settings(): void
    {
        $user = User::factory()->create();
        WebsiteSetting::factory()->create([
            'site_name' => 'Acme Store',
            'seo_title' => 'Quality Products',
        ]);

        $this->actingAs($user)->get(route('settings.website.edit'))
            ->assertOk()
            ->assertSee('Website settings')
            ->assertSee('SEO title')
            ->assertDontSee('data-ds-editable', false)
            ->assertSee('<title>Website Settings | Acme Store</title>', false);
    }

    public function test_authenticated_user_can_update_settings_and_upload_brand_images(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('settings.website.update'), [
            'site_name' => 'Acme Store',
            'seo_title' => 'Acme Store - Quality Products',
            'meta_description' => 'Buy quality products from Acme Store.',
            'meta_keywords' => 'acme, quality products, online store',
            'logo' => UploadedFile::fake()->image('logo.png', 400, 120),
            'favicon' => UploadedFile::fake()->image('favicon.png', 32, 32),
            'featured_image' => UploadedFile::fake()->image('featured.jpg', 1200, 630),
        ])->assertRedirect(route('settings.website.edit'));

        $settings = WebsiteSetting::query()->firstOrFail();

        $this->assertSame('Acme Store', $settings->site_name);
        $this->assertSame('Acme Store - Quality Products', $settings->seo_title);
        $this->assertSame('acme, quality products, online store', $settings->meta_keywords);
        Storage::disk('public')->assertExists($settings->logo_path);
        Storage::disk('public')->assertExists($settings->favicon_path);
        Storage::disk('public')->assertExists($settings->featured_image_path);
        $this->assertSame('media/favicon.png', $settings->favicon_path);
        $this->assertDatabaseHas('media_assets', [
            'path' => $settings->favicon_path,
            'original_name' => 'favicon.png',
        ]);
    }

    public function test_replacing_favicon_removes_the_previous_file_and_media_record(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('settings.website.update'), [
            'site_name' => 'Acme Store',
            'seo_title' => 'Acme Store',
            'favicon' => UploadedFile::fake()->image('first-icon.png', 32, 32),
        ]);

        $oldPath = WebsiteSetting::query()->firstOrFail()->favicon_path;
        $oldMediaAssetId = MediaAsset::query()->where('path', $oldPath)->value('id');
        $this->assertSame('media/favicon.png', $oldPath);

        $this->actingAs($user)->put(route('settings.website.update'), [
            'site_name' => 'Acme Store',
            'seo_title' => 'Acme Store',
            'favicon' => UploadedFile::fake()->image('new-icon.png', 32, 32),
        ])->assertRedirect(route('settings.website.edit'));

        $newPath = WebsiteSetting::query()->firstOrFail()->favicon_path;

        $this->assertSame('media/favicon-2.png', $newPath);
        Storage::disk('public')->assertExists($newPath);
        $this->assertDatabaseHas('media_assets', ['path' => $newPath]);
        $this->assertDatabaseMissing('media_assets', ['id' => $oldMediaAssetId]);

        $this->actingAs($user)->put(route('settings.website.update'), [
            'site_name' => 'Acme Store',
            'seo_title' => 'Acme Store',
            'favicon' => UploadedFile::fake()->image('third-icon.png', 32, 32),
        ])->assertRedirect(route('settings.website.edit'));

        $this->assertSame(
            'media/favicon-3.png',
            WebsiteSetting::query()->firstOrFail()->favicon_path,
        );
    }

    public function test_authenticated_user_can_upload_a_jpeg_favicon(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('settings.website.update'), [
            'site_name' => 'Acme Store',
            'seo_title' => 'Acme Store',
            'favicon' => UploadedFile::fake()->image('favicon.jpg', 64, 64),
        ])->assertRedirect(route('settings.website.edit'))
            ->assertSessionHasNoErrors();

        $faviconPath = WebsiteSetting::query()->firstOrFail()->favicon_path;

        Storage::disk('public')->assertExists($faviconPath);
        $this->assertSame('media/favicon.jpg', $faviconPath);
        $this->assertDatabaseHas('media_assets', ['path' => $faviconPath]);
    }

    public function test_website_setting_validation_rejects_invalid_values(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->put(route('settings.website.update'), [
            'site_name' => '',
            'seo_title' => str_repeat('a', 61),
            'meta_description' => str_repeat('a', 161),
            'logo' => UploadedFile::fake()->create('logo.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrors(['site_name', 'seo_title', 'meta_description', 'logo']);
    }
}
