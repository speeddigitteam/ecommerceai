<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DigitalDownloadController extends Controller
{
    public function show(Order $order, OrderItem $item): StreamedResponse
    {
        abort_unless($item->order_id === $order->id, 404);
        abort_if(in_array($order->status, ['cancelled', 'returned'], true), 403);

        $product = $item->product;
        abort_unless($product && $product->isDigital() && $product->digital_file_path, 404);
        abort_unless(Storage::disk('local')->exists($product->digital_file_path), 404);

        return Storage::disk('local')->download($product->digital_file_path, $product->digital_file_name ?: basename($product->digital_file_path));
    }
}
