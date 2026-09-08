<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_file_manager(): void
    {
        $this->get(route('file-manager.index'))->assertRedirect(route('admin.login'));
    }

    public function test_dashboard_sidebar_contains_file_manager_link(): void
    {
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('File Manager')
            ->assertSee(route('file-manager.index'), false);
    }

    public function test_admin_can_create_folder_upload_browse_rename_download_and_delete_file(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('file-manager.folders.store'), [
            'name' => 'Product Images',
        ])->assertRedirect(route('file-manager.index'));
        Storage::disk('public')->assertExists('media/Product Images');

        $this->actingAs($admin)->post(route('file-manager.upload'), [
            'path' => 'Product Images',
            'files' => [UploadedFile::fake()->image('Original Photo.jpg')],
        ])->assertRedirect(route('file-manager.index', ['path' => 'Product Images']));
        Storage::disk('public')->assertExists('media/Product Images/original-photo.jpg');
        $this->assertDatabaseHas('media_assets', [
            'path' => 'media/Product Images/original-photo.jpg',
            'slug' => 'original-photo.jpg',
            'alt_text' => 'Original Photo',
        ]);

        $this->actingAs($admin)->get(route('file-manager.index', ['path' => 'Product Images']))
            ->assertOk()
            ->assertSee('original-photo.jpg')
            ->assertSee('Product Images')
            ->assertSee('/img/original-photo.jpg', false);
        $this->get('/img/original-photo.jpg')
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg')
            ->assertHeader('cache-control', 'max-age=0, must-revalidate, public');

        $this->actingAs($admin)->patch(route('file-manager.images.seo'), [
            'path' => 'Product Images/original-photo.jpg',
            'filename' => 'ecommerce homepage banner',
            'alt_text' => 'Turquoise product logo for the ecommerce homepage',
            'title' => 'Ecommerce Homepage Logo',
            'caption' => 'Official ecommerce brand logo',
            'description' => 'A turquoise circular brand logo used on the homepage.',
        ])->assertRedirect(route('file-manager.index', ['path' => 'Product Images']));
        Storage::disk('public')->assertExists('media/Product Images/ecommerce-homepage-banner.jpg');
        $this->assertDatabaseHas('media_assets', [
            'path' => 'media/Product Images/ecommerce-homepage-banner.jpg',
            'slug' => 'ecommerce-homepage-banner.jpg',
            'alt_text' => 'Turquoise product logo for the ecommerce homepage',
            'title' => 'Ecommerce Homepage Logo',
        ]);

        $this->actingAs($admin)->patch(route('file-manager.items.rename'), [
            'path' => 'Product Images/ecommerce-homepage-banner.jpg',
            'name' => 'Homepage Banner',
        ])->assertRedirect(route('file-manager.index', ['path' => 'Product Images']));
        Storage::disk('public')->assertExists('media/Product Images/homepage-banner.jpg');
        Storage::disk('public')->assertMissing('media/Product Images/ecommerce-homepage-banner.jpg');
        $this->assertDatabaseHas('media_assets', [
            'path' => 'media/Product Images/homepage-banner.jpg',
            'slug' => 'homepage-banner.jpg',
        ]);

        $this->actingAs($admin)->get(route('file-manager.download', [
            'path' => 'Product Images/homepage-banner.jpg',
        ]))->assertDownload('homepage-banner.jpg');

        $this->actingAs($admin)->delete(route('file-manager.items.destroy'), [
            'path' => 'Product Images/homepage-banner.jpg',
        ])->assertRedirect(route('file-manager.index', ['path' => 'Product Images']));
        Storage::disk('public')->assertMissing('media/Product Images/homepage-banner.jpg');
        $this->assertDatabaseMissing('media_assets', ['path' => 'media/Product Images/homepage-banner.jpg']);
    }

    public function test_file_manager_rejects_unsafe_paths_and_executable_uploads(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();

        $this->actingAs($admin)->get(route('file-manager.index', ['path' => '../private']))
            ->assertStatus(422);

        $this->actingAs($admin)->post(route('file-manager.upload'), [
            'files' => [UploadedFile::fake()->create('malware.php', 2, 'application/x-php')],
        ])->assertSessionHasErrors('files.0');
        Storage::disk('public')->assertMissing('media/malware.php');
    }

    public function test_deleting_a_root_image_does_not_create_a_folder_with_its_filename(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        Storage::disk('public')->put('media/root-image.jpg', 'image-content');

        $this->actingAs($admin)->delete(route('file-manager.items.destroy'), [
            'path' => 'root-image.jpg',
        ])->assertRedirect(route('file-manager.index'));

        Storage::disk('public')->assertMissing('media/root-image.jpg');
        $this->assertFalse(Storage::disk('public')->directoryExists('media/root-image.jpg'));
    }
}
