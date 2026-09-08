<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach ($staticUrls as $entry)
    <url>
        <loc>{{ $entry['url'] }}</loc>
        <lastmod>{{ $entry['lastmod']->toAtomString() }}</lastmod>
    </url>
    @endforeach
    @foreach ($products as $product)
    <url>
        <loc>{{ route('catalog.show', $product->slug) }}</loc>
        <lastmod>{{ $product->updated_at->toAtomString() }}</lastmod>
    </url>
    @endforeach
    @foreach ($posts as $post)
    <url>
        <loc>{{ route('storefront.blog.show', $post->slug) }}</loc>
        <lastmod>{{ $post->updated_at->toAtomString() }}</lastmod>
    </url>
    @endforeach
</urlset>
