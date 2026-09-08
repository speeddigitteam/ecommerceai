<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __invoke(Request $request, string $slug, StorefrontController $storefrontController): View
    {
        $product = Product::query()
            ->where('slug', $slug)
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNotNull('price')
            ->first();

        if ($product) {
            return $storefrontController->show($product);
        }

        $category = Category::query()->where('slug', $slug)->first();

        if ($category) {
            return $storefrontController->category($request, $category);
        }

        $brand = Brand::query()->where('slug', $slug)->first();

        if ($brand) {
            return $storefrontController->brand($request, $brand);
        }

        abort(404);
    }
}
