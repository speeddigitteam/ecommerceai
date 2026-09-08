<?php

namespace Tests\Feature;

use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_blog_management(): void
    {
        $this->get(route('blog.index'))->assertRedirect(route('admin.login'));
        $this->get(route('blog.create'))->assertRedirect(route('admin.login'));
        $this->get(route('blog.categories'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_open_blog_management_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(route('blog.index'))
            ->assertOk()
            ->assertSee('Blog posts')
            ->assertSee('No blog posts found')
            ->assertSeeInOrder(['Post', 'Category', 'Author', 'Status', 'Published', 'Actions']);
    }

    public function test_admin_can_open_blog_submenu_pages(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(route('blog.create'))
            ->assertOk()
            ->assertSee('Add new blog');

        $this->actingAs($admin)->get(route('blog.categories'))
            ->assertOk()
            ->assertSee('Blog categories')
            ->assertSee('Category list');
    }
}
