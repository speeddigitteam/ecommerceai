<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        abort_if($request->user()->isAdmin(), 403);

        $orders = Order::query()->where('customer_email', $request->user()->email);

        return view('customer.dashboard', [
            'user' => $request->user(),
            'recentOrders' => (clone $orders)->with('items')->latest()->limit(5)->get(),
            'orderCount' => (clone $orders)->count(),
            'pendingOrderCount' => (clone $orders)->whereIn('status', ['pending', 'confirmed', 'processing'])->count(),
            'completedOrderCount' => (clone $orders)->where('status', 'completed')->count(),
            'totalSpent' => (float) (clone $orders)->where('status', 'completed')->sum('total'),
        ]);
    }
}
