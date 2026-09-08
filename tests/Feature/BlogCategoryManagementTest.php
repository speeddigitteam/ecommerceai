<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\Category;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_separate_blog_category_with_all_content(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $parent = BlogCategory::factory()->create(['name' => 'News', 'slug' => 'news']);
        Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);

        $this->actingAs($admin)->post(route('blog.categories.store'), [
            'name' => 'Technology',
            'slug' => 'technology',
            'parent_id' => $parent->id,
            'description' => '<p>Top content</p>',
            'extra_description' => '<p>Bottom content</p>',
            'navigation_image' => UploadedFile::fake()->image('technology.png'),
        ])->assertRedirect(route('blog.categories'));

        $category = BlogCategory::query()->where('slug', 'technology')->firstOrFail();
        $this->assertSame($parent->id, $category->parent_id);
        $this->assertSame('<p>Top content</p>', $category->description);
        $this->assertSame('<p>Bottom content</p>', $category->extra_description);
        Storage::disk('public')->assertExists($category->image_path);
        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseCount('blog_categories', 2);
    }

    public function test_admin_can_view_update_and_delete_a_blog_category(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = BlogCategory::factory()->create(['name' => 'Guides', 'slug' => 'guides']);

        $this->actingAs($admin)->get(route('blog.categories.show', $category))->assertOk()->assertSee('Blog category');
        $this->actingAs($admin)->get(route('blog.categories.edit', $category))->assertOk()->assertSee('id="category-edit-form"', false);
        $this->actingAs($admin)->put(route('blog.categories.update', $category), [
            'name' => 'Tutorials',
            'slug' => 'tutorials',
        ])->assertRedirect(route('blog.categories'));
        $this->assertDatabaseHas('blog_categories', ['id' => $category->id, 'name' => 'Tutorials']);

        $this->actingAs($admin)->delete(route('blog.categories.destroy', $category->fresh()))
            ->assertRedirect(route('blog.categories'));
        $this->assertDatabaseMissing('blog_categories', ['id' => $category->id]);
    }

    public function test_blog_category_cannot_be_moved_beneath_its_descendant(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $category = BlogCategory::factory()->create(['name' => 'News', 'slug' => 'news']);
        $child = BlogCategory::factory()->create(['name' => 'Local', 'slug' => 'local', 'parent_id' => $category->id]);

        $this->actingAs($admin)->from(route('blog.categories.edit', $category))->put(route('blog.categories.update', $category), [
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $child->id,
        ])->assertRedirect(route('blog.categories.edit', $category))->assertSessionHasErrors('parent_id');

        $this->assertNull($category->fresh()->parent_id);
    }

    public function test_customer_cannot_manage_blog_categories(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)->get(route('blog.categories'))->assertForbidden();
    }
}
