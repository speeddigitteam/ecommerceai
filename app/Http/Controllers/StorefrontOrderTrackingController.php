<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackOrderRequest;
use App\Models\Order;
use Illuminate\View\View;

class StorefrontOrderTrackingController extends Controller
{
    public function index(): View
    {
        return view('storefront.track-order');
    }

    public function show(TrackOrderRequest $request): View
    {
        $validated = $request->validated();
        $order = Order::query()
            ->with(['items.product', 'shipment.courierIntegration', 'statusHistories'])
            ->where('order_number', $validated['order_number'])
            ->where('customer_phone', $validated['customer_phone'])
            ->first();

        return view('storefront.track-order', [
            'order' => $order,
            'searched' => true,
        ]);
    }
}
