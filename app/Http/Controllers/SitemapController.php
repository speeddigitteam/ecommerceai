<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $products = Product::query()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->select(['slug', 'updated_at'])
            ->orderBy('slug')
            ->get();

        $posts = BlogPost::query()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->select(['slug', 'updated_at'])
            ->orderBy('slug')
            ->get();

        $staticUrls = [
            ['url' => route('storefront.index'), 'lastmod' => now()],
            ['url' => route('storefront.best-selling'), 'lastmod' => now()],
            ['url' => route('storefront.flash-sale'), 'lastmod' => now()],
            ['url' => route('storefront.new-arrivals'), 'lastmod' => now()],
            ['url' => route('storefront.latest-products'), 'lastmod' => now()],
            ['url' => route('storefront.blog.index'), 'lastmod' => now()],
        ];

        $xml = view('sitemap', [
            'staticUrls' => $staticUrls,
            'products' => $products,
            'posts' => $posts,
        ])->render();

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }
}
