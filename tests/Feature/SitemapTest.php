<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_is_valid_xml_and_includes_static_pages(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/xml');
        $this->assertNotFalse(simplexml_load_string($response->getContent()));
        $response->assertSee(route('storefront.index'), false);
    }

    public function test_sitemap_includes_published_products_and_posts_only(): void
    {
        $publicProduct = Product::factory()->create(['status' => 'published', 'visibility' => 'public']);
        $draftProduct = Product::factory()->create(['status' => 'draft']);
        $publicPost = BlogPost::factory()->create(['status' => 'published', 'visibility' => 'public', 'published_at' => now()->subDay()]);
        $draftPost = BlogPost::factory()->create(['status' => 'draft']);

        $response = $this->get(route('sitemap'));

        $response->assertSee(route('catalog.show', $publicProduct->slug), false);
        $response->assertDontSee(route('catalog.show', $draftProduct->slug), false);
        $response->assertSee(route('storefront.blog.show', $publicPost->slug), false);
        $response->assertDontSee(route('storefront.blog.show', $draftPost->slug), false);
    }
}
