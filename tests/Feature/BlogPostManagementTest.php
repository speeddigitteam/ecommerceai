<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Product;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogPostManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_complete_blog_form(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        BlogCategory::factory()->create(['name' => 'Technology']);

        $this->actingAs($admin)->get(route('blog.create'))->assertOk()
            ->assertSee('Add new blog')
            ->assertSee('id="blog-content"', false)
            ->assertSee('Blog categories')
            ->assertSee('Featured image')
            ->assertSee('Gallery')
            ->assertSee('Search engine optimization')
            ->assertSee('Focus keyword')
            ->assertSee('Publish date');
    }

    public function test_admin_can_create_a_blog_post_with_categories_media_and_seo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = BlogCategory::factory()->create();
        Product::factory()->create(['title' => 'Separate Product']);

        $this->actingAs($admin)->post(route('blog.store'), [
            'title' => 'Laravel Store Guide',
            'content' => '<p>Complete guide content.</p>',
            'excerpt' => 'A short guide.',
            'category_ids' => [$category->id],
            'status' => 'published',
            'visibility' => 'public',
            'tags' => 'laravel, ecommerce',
            'focus_keyword' => 'laravel store',
            'seo_title' => 'Laravel Store Guide',
            'meta_description' => 'Build a Laravel ecommerce store with this guide.',
            'featured_image' => UploadedFile::fake()->image('guide.jpg'),
            'gallery' => [UploadedFile::fake()->image('detail.jpg')],
        ])->assertRedirect(route('blog.index'));

        $post = BlogPost::query()->where('slug', 'laravel-store-guide')->firstOrFail();
        $this->assertSame($admin->id, $post->author_id);
        $this->assertSame(['laravel', 'ecommerce'], $post->tags);
        $this->assertTrue($post->categories->contains($category));
        $this->assertNotNull($post->published_at);
        Storage::disk('public')->assertExists($post->featured_image_path);
        Storage::disk('public')->assertExists($post->gallery_paths[0]);
        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseCount('blog_posts', 1);
    }

    public function test_admin_can_preview_update_and_delete_a_blog_post(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $post = BlogPost::factory()->create(['author_id' => $admin->id]);

        $this->actingAs($admin)->get(route('blog.show', $post))->assertOk()->assertSee($post->title);
        $this->actingAs($admin)->put(route('blog.update', $post), [
            'title' => 'Updated Blog',
            'slug' => 'updated-blog',
            'status' => 'draft',
            'visibility' => 'private',
        ])->assertRedirect(route('blog.index'));
        $this->assertDatabaseHas('blog_posts', ['id' => $post->id, 'title' => 'Updated Blog']);

        $this->actingAs($admin)->delete(route('blog.destroy', $post->fresh()))->assertRedirect(route('blog.index'));
        $this->assertDatabaseMissing('blog_posts', ['id' => $post->id]);
    }
}
