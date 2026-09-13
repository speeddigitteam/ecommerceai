<?php

namespace App\Services;

use App\Jobs\SendAutomatedEmail;
use App\Models\Order;
use App\Models\User;
use App\OrderStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderStatusManager
{
    public function transition(
        Order $order,
        OrderStatus $target,
        ?User $user = null,
        ?string $note = null,
        bool $courierConfirmedCancellation = false,
    ): Order {
        $updatedOrder = DB::transaction(function () use ($order, $target, $user, $note, $courierConfirmedCancellation): Order {
            $lockedOrder = Order::query()->with('items.product')->lockForUpdate()->findOrFail($order->id);
            $current = OrderStatus::from($lockedOrder->status);

            if (! in_array($target, $current->next(), true)) {
                throw ValidationException::withMessages([
                    'status' => "Order cannot move from {$current->label()} to {$target->label()}.",
                ]);
            }

            if ($target === OrderStatus::Cancelled) {
                if ($lockedOrder->shipment()->exists() && ! $courierConfirmedCancellation) {
                    throw ValidationException::withMessages([
                        'status' => 'A submitted courier parcel must be cancelled with the courier before cancelling this order.',
                    ]);
                }
                $this->restoreStock($lockedOrder);
            }

            $lockedOrder->update(['status' => $target->value]);
            $lockedOrder->statusHistories()->create([
                'user_id' => $user?->id,
                'from_status' => $current->value,
                'to_status' => $target->value,
                'note' => $note,
            ]);

            return $lockedOrder->fresh(['items', 'shipment', 'statusHistories.user']);
        });

        $templateKey = match ($target) {
            OrderStatus::Shipped => 'shipping',
            OrderStatus::Completed => 'delivery',
            OrderStatus::Cancelled => 'cancellation',
            OrderStatus::Returned => 'return',
            default => 'order-status',
        };
        SendAutomatedEmail::dispatch($templateKey, $updatedOrder->id)->afterCommit();

        return $updatedOrder;
    }

    private function restoreStock(Order $order): void
    {
        if ($order->stock_restored_at !== null) {
            return;
        }

        foreach ($order->items as $item) {
            $item->product?->increment('stock_quantity', $item->quantity);
        }

        $order->update(['stock_restored_at' => now()]);
    }
}
