<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontBlogTest extends TestCase
{
    use RefreshDatabase;

    public function test_footer_links_to_public_blog_listing(): void
    {
        $this->get(route('storefront.index'))
            ->assertOk()
            ->assertSee('href="'.route('storefront.blog.index').'"', false);
    }

    public function test_only_published_public_posts_are_visible(): void
    {
        $published = BlogPost::factory()->create([
            'title' => 'Visible Story',
            'content' => '<h2>First section</h2><p>Story content.</p>',
            'status' => 'published',
            'visibility' => 'public',
            'published_at' => now(),
        ]);
        $draft = BlogPost::factory()->create([
            'title' => 'Hidden Draft',
            'status' => 'draft',
            'visibility' => 'public',
            'published_at' => null,
        ]);

        $this->get(route('storefront.blog.index'))
            ->assertOk()
            ->assertSee($published->title)
            ->assertDontSee($draft->title);

        $this->get(route('storefront.blog.show', $published))
            ->assertOk()
            ->assertSee($published->title)
            ->assertSee('Min Read')
            ->assertSee('id="blog-table-of-contents"', false)
            ->assertSee('Related articles');
        $this->get(route('storefront.blog.show', $draft))->assertNotFound();
    }

    public function test_blog_listing_can_filter_by_search(): void
    {
        BlogPost::factory()->create(['title' => 'Laravel Shopping Guide', 'status' => 'published', 'visibility' => 'public', 'published_at' => now()]);
        BlogPost::factory()->create(['title' => 'Fashion Trends', 'status' => 'published', 'visibility' => 'public', 'published_at' => now()]);

        $this->get(route('storefront.blog.index', ['search' => 'Laravel']))
            ->assertOk()
            ->assertSee('Laravel Shopping Guide')
            ->assertDontSee('Fashion Trends')
            ->assertSee('Blog Highlights &amp; More', false);
    }
}
