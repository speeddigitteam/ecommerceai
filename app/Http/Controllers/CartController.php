<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\WholesalePricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly WholesalePricing $wholesalePricing) {}

    public function index(Request $request): View
    {
        return view('storefront.cart', $this->cartData($request));
    }

    public function store(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        abort_unless($product->status === 'published' && $product->visibility === 'public' && $product->price !== null && $product->stock_quantity > 0, 404);
        $product->loadMissing('variants');
        $variant = $this->resolveVariant($product, $request->integer('variant_id'));
        abort_if($product->variants->isNotEmpty() && ! $variant, 422, 'Please select a product option.');
        $availableStock = $variant?->stock_quantity ?? $product->stock_quantity;

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$availableStock],
            'checkout' => ['sometimes', 'boolean'],
        ]);
        $cartKey = $this->cartKey($product->id, $variant?->id);
        $cart = $request->session()->get('cart', []);
        $cart[$cartKey] = min(($cart[$cartKey] ?? 0) + $validated['quantity'], $availableStock);
        $request->session()->put('cart', $cart);

        if ($request->expectsJson() && ! ($validated['checkout'] ?? false)) {
            return response()->json([
                'cartCount' => (int) collect($cart)->sum(),
                'productQuantity' => (int) $cart[$cartKey],
            ]);
        }

        $redirectRoute = ($validated['checkout'] ?? false) ? 'checkout.create' : 'cart.index';

        return to_route($redirectRoute)->with('status', 'Product added to your cart.');
    }

    public function update(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $variant = $this->resolveVariant($product, $request->integer('variant_id'));
        $availableStock = $variant?->stock_quantity ?? $product->stock_quantity;
        $validated = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:'.$availableStock]]);
        $cartKey = $this->cartKey($product->id, $variant?->id);
        $cart = $request->session()->get('cart', []);
        if ($validated['quantity'] === 0) {
            unset($cart[$cartKey]);
        } else {
            $cart[$cartKey] = $validated['quantity'];
        }
        $request->session()->put('cart', $cart);

        if ($request->expectsJson()) {
            $cartData = $this->cartData($request);

            return response()->json([
                'quantity' => (int) ($cart[$cartKey] ?? 0),
                'subtotal' => $cartData['subtotal'],
                'cartCount' => (int) collect($cart)->sum(),
            ]);
        }

        return to_route('cart.index');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $variant = $this->resolveVariant($product, $request->integer('variant_id'));
        $cartKey = $this->cartKey($product->id, $variant?->id);
        $cart = $request->session()->get('cart', []);
        unset($cart[$cartKey]);
        $request->session()->put('cart', $cart);

        return to_route('cart.index')->with('status', 'Product removed from your cart.');
    }

    /** @return array{items: Collection<int, array{product: Product, variant: ?ProductVariant, quantity: int, unitPrice: float, pricingType: string, minimumWholesaleQuantity: int|null, lineTotal: float}>, subtotal: float} */
    public function cartData(Request $request): array
    {
        $cart = $request->session()->get('cart', []);
        $productIds = collect(array_keys($cart))->map(fn (int|string $key): int => (int) Str::before((string) $key, ':'))->unique();
        $products = Product::query()->with(['categories', 'wholesalePriceTiers', 'variants.wholesalePriceTiers'])->whereIn('id', $productIds)->get()->keyBy('id');
        $customer = $request->user();
        $items = collect($cart)->map(function (int $quantity, int|string $cartKey) use ($products, $customer): ?array {
            [$productId, $variantId] = array_pad(explode(':', (string) $cartKey, 2), 2, null);
            $product = $products->get((int) $productId);
            if (! $product || $product->status !== 'published' || $product->visibility !== 'public') {
                return null;
            }
            $variant = $variantId !== null ? $product->variants->firstWhere('id', (int) $variantId) : null;
            if ($variantId !== null && ! $variant) {
                return null;
            }
            $retailPrice = $variant?->current_price ?? $product->current_price;
            $stock = $variant?->stock_quantity ?? $product->stock_quantity;
            if ($retailPrice === null) {
                return null;
            }
            $safeQuantity = min($quantity, $stock);
            $pricing = $this->wholesalePricing->resolve($product, $variant, $safeQuantity, $customer);

            return $safeQuantity > 0 ? [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $safeQuantity,
                'unitPrice' => $pricing['unit_price'],
                'pricingType' => $pricing['pricing_type'],
                'minimumWholesaleQuantity' => $pricing['minimum_quantity'],
                'lineTotal' => $pricing['unit_price'] * $safeQuantity,
            ] : null;
        })->filter()->values();

        return ['items' => $items, 'subtotal' => (float) $items->sum('lineTotal')];
    }

    private function ensureWholesaleMinimum(Product $product, ?ProductVariant $variant, int $quantity, Request $request): void
    {
        $product->loadMissing(['wholesalePriceTiers', 'variants.wholesalePriceTiers']);
        $pricing = $this->wholesalePricing->resolve($product, $variant, $quantity, $request->user());
        if ($pricing['minimum_quantity'] !== null && $quantity < $pricing['minimum_quantity']) {
            throw ValidationException::withMessages([
                'quantity' => 'The minimum wholesale quantity is '.$pricing['minimum_quantity'].'.',
            ]);
        }
    }

    private function resolveVariant(Product $product, ?int $variantId): ?ProductVariant
    {
        if (! $variantId) {
            return null;
        }

        return $product->variants->firstWhere('id', $variantId);
    }

    private function cartKey(int $productId, ?int $variantId): string
    {
        return $variantId ? "{$productId}:{$variantId}" : (string) $productId;
    }
}
