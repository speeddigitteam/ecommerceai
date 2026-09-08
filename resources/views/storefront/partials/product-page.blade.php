@foreach ($products as $product)
    <x-store-product-card :product="$product" />
@endforeach
@php
    $nextProductPageUrl = $products->nextPageUrl();
    if ($nextProductPageUrl) {
        $nextProductPageUrl .= str_contains($nextProductPageUrl, '?') ? '&infinite=1' : '?infinite=1';
    }
@endphp
<span data-infinite-meta data-next-url="{{ $nextProductPageUrl }}" hidden></span>
