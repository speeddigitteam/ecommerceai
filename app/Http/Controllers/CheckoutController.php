<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDirectOrderRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\DeliveryCharges;
use App\Services\WholesalePricing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly DeliveryCharges $deliveryCharges,
        private readonly WholesalePricing $wholesalePricing,
    ) {}

    public function create(Request $request, CartController $cartController): View|RedirectResponse
    {
        $cart = $cartController->cartData($request);

        return $cart['items']->isEmpty() ? to_route('storefront.index') : view('storefront.checkout', [
            ...$cart,
            'deliveryRates' => $this->deliveryCharges->rates($cart['items']->pluck('product')),
            'hasPhysicalItem' => $cart['items']->contains(fn (array $item): bool => ! $item['product']->isDigital()),
        ]);
    }

    public function store(StoreOrderRequest $request, CartController $cartController): RedirectResponse
    {
        $cart = $cartController->cartData($request);
        if ($cart['items']->isEmpty()) {
            return to_route('cart.index')->withErrors(['cart' => 'Your cart is empty or its products are unavailable.']);
        }

        $validated = $request->validated();
        $hasPhysicalItem = $cart['items']->contains(fn (array $item): bool => ! $item['product']->isDigital());
        $shippingCost = $hasPhysicalItem ? $this->deliveryCharges->rates($cart['items']->pluck('product'))[$validated['delivery_area']] : 0;

        $order = DB::transaction(function () use ($validated, $cart, $shippingCost): Order {
            foreach ($cart['items'] as $item) {
                abort_if($item['quantity'] > $item['product']->fresh()->stock_quantity, 422, 'A product no longer has enough stock.');
            }
            $order = $this->createOrder([
                ...collect($validated)->only(['customer_name', 'customer_phone', 'customer_email', 'shipping_address', 'customer_note', 'payment_method'])->all(),
                'subtotal' => $cart['subtotal'],
                'shipping_cost' => $shippingCost,
                'delivery_area' => $validated['delivery_area'] ?? null,
                'total' => $cart['subtotal'] + $shippingCost,
                'status' => 'pending',
                'order_type' => $cart['items']->contains(fn (array $item): bool => $item['pricingType'] === 'wholesale') ? 'wholesale' : 'retail',
            ]);
            foreach ($cart['items'] as $item) {
                $order->items()->create(['product_id' => $item['product']->id, 'product_title' => $item['product']->title, 'sku' => $item['product']->sku, 'unit_price' => $item['unitPrice'], 'pricing_type' => $item['pricingType'], 'quantity' => $item['quantity'], 'line_total' => $item['lineTotal']]);
                if (! $item['product']->isDigital()) {
                    $item['product']->decrement('stock_quantity', $item['quantity']);
                }
            }

            return $order;
        });
        $request->session()->forget('cart');

        return to_route('checkout.success', ['order' => $order->order_number]);
    }

    public function success(Order $order): View
    {
        return view('storefront.success', ['order' => $order->load('items.product')]);
    }

    public function directStore(StoreDirectOrderRequest $request, Product $product): RedirectResponse
    {
        abort_unless($product->status === 'published' && $product->visibility === 'public' && $product->current_price !== null, 404);

        $validated = $request->validated();
        $shippingCost = $product->isDigital() ? 0 : $this->deliveryCharges->rates([$product])[$validated['delivery_area']];
        $quantity = (int) $validated['quantity'];

        $order = DB::transaction(function () use ($validated, $product, $quantity, $shippingCost): Order {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            abort_if($quantity > $lockedProduct->stock_quantity, 422, 'This product no longer has enough stock.');

            $lockedProduct->load('wholesalePriceTiers');
            $pricing = $this->wholesalePricing->resolve($lockedProduct, null, $quantity, auth()->user());
            if ($pricing['minimum_quantity'] !== null && $quantity < $pricing['minimum_quantity']) {
                abort(422, 'The wholesale minimum quantity is '.$pricing['minimum_quantity'].'.');
            }
            $subtotal = $pricing['unit_price'] * $quantity;
            $order = $this->createOrder([
                ...collect($validated)->only(['customer_name', 'customer_phone', 'customer_email', 'shipping_address', 'customer_note', 'payment_method'])->all(),
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'delivery_area' => $validated['delivery_area'] ?? null,
                'total' => $subtotal + $shippingCost,
                'status' => 'pending',
                'order_type' => $pricing['pricing_type'],
            ]);
            $order->items()->create([
                'product_id' => $lockedProduct->id,
                'product_title' => $lockedProduct->title,
                'sku' => $lockedProduct->sku,
                'unit_price' => $pricing['unit_price'],
                'pricing_type' => $pricing['pricing_type'],
                'quantity' => $quantity,
                'line_total' => $subtotal,
            ]);
            if (! $lockedProduct->isDigital()) {
                $lockedProduct->decrement('stock_quantity', $quantity);
            }

            return $order;
        });

        return to_route('checkout.success', ['order' => $order->order_number]);
    }

    /** @param array<string, mixed> $attributes */
    private function createOrder(array $attributes): Order
    {
        $order = Order::query()->create([
            ...$attributes,
            'shipping_address' => $attributes['shipping_address'] ?? '',
            'user_id' => auth()->id(),
            'order_number' => 'TEMP-'.Str::uuid(),
        ]);
        $order->update([
            'order_number' => 'ORD'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
        ]);

        return $order;
    }
}
