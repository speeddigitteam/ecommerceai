<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductReviewRequest;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Services\MediaLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductReviewController extends Controller
{
    public function __construct(private readonly MediaLibrary $mediaLibrary) {}

    public function index(Request $request): View
    {
        $user = $this->customer($request);
        $orders = $user->reviewableOrders()->with('items.product')->latest()->get();
        $products = $orders->flatMap(fn ($order) => $order->items->pluck('product'))
            ->filter()
            ->unique('id')
            ->values();
        $reviews = $user->reviews()->get()->keyBy('product_id');

        return view('customer.reviews', compact('products', 'reviews'));
    }

    public function store(StoreProductReviewRequest $request, Product $product): RedirectResponse
    {
        $user = $this->customer($request);
        $order = $user->completedOrderForProduct($product);
        abort_unless($order, 403, 'Only customers with a completed purchase can review this product.');

        if ($user->reviews()->where('product_id', $product->id)->exists()) {
            throw ValidationException::withMessages(['review' => 'You have already reviewed this product.']);
        }

        $imagePaths = $this->storeImages($request);
        $user->reviews()->create([
            'product_id' => $product->id,
            'order_id' => $order->id,
            'rating' => $request->integer('rating'),
            'body' => $request->string('body')->trim()->toString(),
            'image_paths' => $imagePaths,
        ]);

        return to_route('customer.reviews.index')->with('status', 'Review added successfully.');
    }

    public function update(StoreProductReviewRequest $request, ProductReview $review): RedirectResponse
    {
        $user = $this->customer($request);
        abort_unless($review->user_id === $user->id, 403);
        abort_unless($user->completedOrderForProduct($review->product), 403);

        $existingPaths = $review->image_paths ?? [];
        $removedPaths = array_values(array_intersect($existingPaths, $request->array('remove_images')));
        $keptPaths = array_values(array_diff($existingPaths, $removedPaths));

        if (count($keptPaths) + count($request->file('images', [])) > 5) {
            throw ValidationException::withMessages(['images' => 'A review may contain up to 5 images.']);
        }

        $newPaths = $this->storeImages($request);
        $review->update([
            'rating' => $request->integer('rating'),
            'body' => $request->string('body')->trim()->toString(),
            'image_paths' => [...$keptPaths, ...$newPaths],
        ]);
        $this->mediaLibrary->delete($removedPaths);

        return to_route('customer.reviews.index')->with('status', 'Review updated successfully.');
    }

    public function destroy(Request $request, ProductReview $review): RedirectResponse
    {
        $user = $this->customer($request);
        abort_unless($review->user_id === $user->id, 403);

        $this->mediaLibrary->delete($review->image_paths ?? []);
        $review->delete();

        return to_route('customer.reviews.index')->with('status', 'Review deleted.');
    }

    private function customer(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();
        abort_if($user->isAdmin(), 403);

        return $user;
    }

    /** @return list<string> */
    private function storeImages(StoreProductReviewRequest $request): array
    {
        return collect($request->file('images', []))
            ->map(fn ($image): string => $this->mediaLibrary->storeImage($image, 'reviews', 'Customer review'))
            ->values()
            ->all();
    }
}
