<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAdminOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\CourierIntegration;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WebsiteSetting;
use App\OrderStatus;
use App\Services\DeliveryCharges;
use App\Services\OrderStatusManager;
use App\Services\SteadfastCourier;
use App\Support\Code128Barcode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class OrderController extends Controller
{
    /** @var array<string, int> */
    private const DELIVERY_CHARGES = ['dhaka_city' => 50, 'dhaka_outside' => 80, 'outside_dhaka' => 100];

    public function index(Request $request): View
    {
        $statusCounts = Order::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('orders.index', [
            'orders' => $this->filteredOrders($request)->with('items.product')->latest()->paginate(15)->withQueryString(),
            'statusCounts' => $statusCounts,
            'unreadOrderCount' => Order::query()->whereNull('viewed_at')->count(),
        ]);
    }

    public function create(): View
    {
        return view('orders.create', [
            'products' => Product::query()->where('stock_quantity', '>', 0)->orderBy('title')->get(),
        ]);
    }

    public function store(StoreAdminOrderRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $order = DB::transaction(function () use ($validated): Order {
            $product = Product::query()->lockForUpdate()->findOrFail($validated['product_id']);
            abort_if($validated['quantity'] > $product->stock_quantity || $product->current_price === null, 422, 'This product does not have enough stock.');
            $subtotal = $product->current_price * $validated['quantity'];
            $shippingCost = app(DeliveryCharges::class)->rates([$product])[$validated['delivery_area']];
            $order = Order::query()->create([
                ...collect($validated)->only(['customer_name', 'customer_phone', 'customer_email', 'shipping_address', 'customer_note'])->all(),
                'order_number' => 'TEMP-'.Str::uuid(),
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'delivery_area' => $validated['delivery_area'],
                'total' => $subtotal + $shippingCost,
                'payment_method' => 'cash_on_delivery',
                'status' => OrderStatus::Pending->value,
            ]);
            $order->update(['order_number' => 'ORD'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT)]);
            $order->items()->create(['product_id' => $product->id, 'product_title' => $product->title, 'sku' => $product->sku, 'unit_price' => $product->current_price, 'quantity' => $validated['quantity'], 'line_total' => $subtotal]);
            $product->decrement('stock_quantity', $validated['quantity']);

            return $order;
        });

        return to_route('orders.show', $order)->with('status', 'Order created successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        $orders = $this->filteredOrders($request)->with('items')->latest()->get();

        return response()->streamDownload(function () use ($orders): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Order', 'Customer', 'Phone', 'Products', 'Subtotal', 'Delivery', 'Total', 'Status', 'Date']);
            foreach ($orders as $order) {
                fputcsv($output, [$order->order_number, $order->customer_name, $order->customer_phone, $order->items->pluck('product_title')->join(', '), $order->subtotal, $order->shipping_cost, $order->total, $order->status, $order->created_at->toDateTimeString()]);
            }
            fclose($output);
        }, 'orders-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function show(Order $order): View
    {
        if ($order->viewed_at === null) {
            $order->update(['viewed_at' => now()]);
        }

        $customerOrderCount = Order::query()
            ->where('customer_phone', $order->customer_phone)
            ->when($order->customer_email, fn ($query) => $query->orWhere('customer_email', $order->customer_email))
            ->count();

        return view('orders.show', [
            'order' => $order->load(['items', 'shipment.courierIntegration', 'statusHistories.user']),
            'customerOrderCount' => $customerOrderCount,
            'nextStatuses' => OrderStatus::from($order->status)->next(),
        ]);
    }

    public function printLabel(Order $order, Code128Barcode $barcode): View
    {
        return view('orders.print-label', [
            'order' => $order->load(['items', 'shipment']),
            'settings' => WebsiteSetting::query()->first(),
            'barcodeSvg' => $barcode->svg($order->order_number),
        ]);
    }

    public function notifications(): JsonResponse
    {
        $notifications = Cache::remember('admin.order.notifications', 10, function (): array {
            $unreadOrders = Order::query()->whereNull('viewed_at')->latest()->get();
            $recentViewedOrders = Order::query()->whereNotNull('viewed_at')->latest()->limit(5)->get();
            $orders = $unreadOrders->concat($recentViewedOrders)->sortByDesc('created_at')->values();

            return [
                'unread_count' => $unreadOrders->count(),
                'orders' => $orders->map(fn (Order $order): array => [
                    'number' => $order->order_number, 'customer' => $order->customer_name, 'total' => $order->total,
                    'time' => $order->created_at->diffForHumans(), 'unread' => $order->viewed_at === null,
                    'url' => route('orders.show', $order),
                ])->all(),
            ];
        });

        return response()->json($notifications);
    }

    public function edit(Order $order): View
    {
        return view('orders.edit', ['order' => $order->load('items.product')]);
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $validated = $request->validated();
        $items = $validated['items'] ?? null;
        unset($validated['items']);

        DB::transaction(function () use ($order, $validated, $items): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($items !== null) {
                if ($lockedOrder->shipment()->exists() || in_array($lockedOrder->status, [
                    OrderStatus::Shipped->value,
                    OrderStatus::Completed->value,
                    OrderStatus::Cancelled->value,
                    OrderStatus::Returned->value,
                ], true)) {
                    throw ValidationException::withMessages([
                        'items' => 'Order items cannot be changed after courier submission or after the order reaches a final delivery stage.',
                    ]);
                }

                $orderItems = $lockedOrder->items()->with('product')->lockForUpdate()->get()->keyBy('id');

                foreach ($items as $itemData) {
                    $orderItem = $orderItems->get($itemData['id']);
                    $newQuantity = (int) $itemData['quantity'];
                    $quantityDifference = $newQuantity - $orderItem->quantity;
                    $product = $orderItem->product_id
                        ? Product::query()->lockForUpdate()->find($orderItem->product_id)
                        : null;

                    if ($quantityDifference > 0) {
                        if ($product === null || $quantityDifference > $product->stock_quantity) {
                            $availableStock = $product?->stock_quantity ?? 0;

                            throw ValidationException::withMessages([
                                'items' => "Only {$availableStock} additional unit(s) are available for {$orderItem->product_title}.",
                            ]);
                        }

                        $product->decrement('stock_quantity', $quantityDifference);
                    } elseif ($quantityDifference < 0 && $product !== null) {
                        $product->increment('stock_quantity', abs($quantityDifference));
                    }

                    $orderItem->update([
                        'quantity' => $newQuantity,
                        'line_total' => (float) $orderItem->unit_price * $newQuantity,
                    ]);
                }

                $subtotal = (float) $lockedOrder->items()->sum('line_total');
                $validated['subtotal'] = $subtotal;
                $validated['total'] = $subtotal + (float) $lockedOrder->shipping_cost;
            }

            $lockedOrder->update($validated);
        });

        return to_route('orders.show', $order)->with('status', 'Order updated successfully.');
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order, OrderStatusManager $statusManager): RedirectResponse
    {
        $validated = $request->validated();
        $statusManager->transition(
            $order,
            OrderStatus::from($validated['status']),
            $request->user(),
            $validated['note'] ?? null,
        );

        return to_route('orders.show', $order)->with('status', 'Order status updated successfully.');
    }

    public function sendToCourier(Order $order, SteadfastCourier $steadfast): RedirectResponse
    {
        abort_unless($order->status === OrderStatus::ReadyToShip->value, 422, 'Only ready-to-ship orders can be sent to a courier.');
        abort_if($order->shipment()->exists(), 422, 'This order has already been sent to a courier.');

        $integration = CourierIntegration::query()
            ->where('provider', 'steadfast')
            ->where('is_active', true)
            ->latest()
            ->firstOrFail();

        try {
            $response = $steadfast->createOrder($integration, $order->load('items'));
            if ((int) ($response['status'] ?? 0) !== 200 || blank(data_get($response, 'consignment.consignment_id'))) {
                throw new RuntimeException((string) ($response['message'] ?? 'Steadfast rejected the parcel.'));
            }

            $order->shipment()->create([
                'courier_integration_id' => $integration->id,
                'provider' => 'steadfast',
                'external_id' => (string) data_get($response, 'consignment.consignment_id'),
                'tracking_code' => data_get($response, 'consignment.tracking_code'),
                'status' => 'submitted',
                'raw_response' => $response,
                'sent_at' => now(),
                'last_synced_at' => now(),
            ]);

            return to_route('orders.show', $order)->with('status', 'Order sent to Steadfast successfully.');
        } catch (Throwable $exception) {
            report($exception);

            return to_route('orders.show', $order)->with('error', 'Steadfast could not create the parcel. The order was not changed.');
        }
    }

    public function syncCourier(Order $order, SteadfastCourier $steadfast, OrderStatusManager $statusManager): RedirectResponse
    {
        $shipment = $order->shipment()->with('courierIntegration')->firstOrFail();
        abort_unless($shipment->provider === 'steadfast' && $shipment->courierIntegration, 422);

        try {
            $response = $steadfast->status($shipment->courierIntegration, $shipment->external_id);
            $courierStatus = (string) ($response['delivery_status'] ?? 'unknown');
            $shipment->update([
                'status' => $courierStatus,
                'raw_response' => $response,
                'last_synced_at' => now(),
            ]);

            $this->applyCourierStatus($order->fresh(), $courierStatus, $statusManager, auth()->user());

            return to_route('orders.show', $order)->with('status', 'Courier status synchronized successfully.');
        } catch (Throwable $exception) {
            report($exception);

            return to_route('orders.show', $order)->with('error', 'Courier status could not be synchronized. Try again later.');
        }
    }

    public function destroy(Order $order): RedirectResponse
    {
        $order->delete();

        return to_route('orders.index')->with('status', 'Order deleted successfully.');
    }

    private function filteredOrders(Request $request): Builder
    {
        return Order::query()
            ->select('orders.*')
            ->selectSub(function ($query): void {
                $query->from('orders as customer_orders')
                    ->selectRaw('count(*)')
                    ->where(function ($query): void {
                        $query->whereColumn('customer_orders.customer_phone', 'orders.customer_phone')
                            ->orWhereColumn('customer_orders.customer_email', 'orders.customer_email');
                    });
            }, 'customer_order_count')
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->value();
                $query->where(function ($query) use ($search): void {
                    $query->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('date'), fn ($query) => $query->whereDate('created_at', $request->string('date')->value()))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->when($request->filled('delivery_area'), fn ($query) => $query->where(function ($query) use ($request): void {
                $query->where('delivery_area', $request->string('delivery_area')->value())
                    ->orWhere(fn ($legacy) => $legacy->whereNull('delivery_area')->where('shipping_cost', self::DELIVERY_CHARGES[$request->string('delivery_area')->value()] ?? -1));
            }));
    }

    private function applyCourierStatus(Order $order, string $courierStatus, OrderStatusManager $statusManager, ?User $user): void
    {
        if (in_array($courierStatus, ['picked_up', 'in_transit'], true) && $order->status === OrderStatus::ReadyToShip->value) {
            $statusManager->transition($order, OrderStatus::Shipped, $user, "Courier status: {$courierStatus}.");
            $order->shipment()->update(['picked_up_at' => now()]);
        }

        if ($courierStatus === 'delivered') {
            if ($order->status === OrderStatus::ReadyToShip->value) {
                $order = $statusManager->transition($order, OrderStatus::Shipped, $user, 'Courier confirmed shipment.');
            }
            if ($order->status === OrderStatus::Shipped->value) {
                $statusManager->transition($order, OrderStatus::Completed, $user, 'Courier confirmed delivery.');
                $order->shipment()->update(['delivered_at' => now()]);
            }
        }

        if ($courierStatus === 'cancelled' && $order->status === OrderStatus::ReadyToShip->value) {
            $statusManager->transition(
                $order,
                OrderStatus::Cancelled,
                $user,
                'Courier confirmed parcel cancellation.',
                courierConfirmedCancellation: true,
            );
        }

        if (in_array($courierStatus, ['returned', 'partial_delivered'], true) && $order->status === OrderStatus::Shipped->value) {
            $statusManager->transition($order, OrderStatus::Returned, $user, "Courier status: {$courierStatus}.");
            $order->shipment()->update(['returned_at' => now()]);
        }
    }
}
