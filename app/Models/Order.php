<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Cache;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['delivery_area', 'user_id', 'order_number', 'order_type', 'customer_name', 'customer_phone', 'customer_email', 'shipping_address', 'customer_note', 'subtotal', 'shipping_cost', 'total', 'payment_method', 'status', 'stock_restored_at', 'viewed_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(OrderShipment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest();
    }

    protected static function booted(): void
    {
        static::created(function (Order $order): void {
            $order->statusHistories()->create([
                'user_id' => auth()->id(),
                'from_status' => null,
                'to_status' => $order->status,
                'note' => 'Order created.',
            ]);
        });
        static::saved(function (): void {
            Cache::forget('admin.order.notifications');
        });
        static::deleted(function (): void {
            Cache::forget('admin.order.notifications');
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['subtotal' => 'decimal:2', 'shipping_cost' => 'decimal:2', 'total' => 'decimal:2', 'stock_restored_at' => 'datetime', 'viewed_at' => 'datetime'];
    }
}
