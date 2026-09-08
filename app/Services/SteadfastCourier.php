<?php

namespace App\Services;

use App\Models\CourierIntegration;
use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class SteadfastCourier
{
    public function balance(CourierIntegration $integration): float
    {
        return (float) $this->client($integration)
            ->get(rtrim($integration->base_url, '/').'/get_balance')
            ->throw()
            ->json('current_balance', 0);
    }

    /** @return array<string, mixed> */
    public function createOrder(CourierIntegration $integration, Order $order): array
    {
        return $this->client($integration)
            ->post(rtrim($integration->base_url, '/').'/create_order', [
                'invoice' => $order->order_number,
                'recipient_name' => $order->customer_name,
                'recipient_phone' => $order->customer_phone,
                'recipient_address' => $order->shipping_address,
                'cod_amount' => (float) $order->total,
                'note' => $order->items->map(fn ($item): string => "{$item->product_title} x {$item->quantity}")->join(', '),
            ])
            ->throw()
            ->json();
    }

    /** @return array<string, mixed> */
    public function status(CourierIntegration $integration, string $externalId): array
    {
        return $this->client($integration)
            ->get(rtrim($integration->base_url, '/').'/status_by_cid/'.$externalId)
            ->throw()
            ->json();
    }

    private function client(CourierIntegration $integration): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withHeaders([
                'Api-Key' => $integration->api_key,
                'Secret-Key' => $integration->secret_key,
            ])
            ->timeout(15)
            ->connectTimeout(5);
    }
}
