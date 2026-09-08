<?php

namespace App\Models;

use Database\Factories\OrderShipmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderShipment extends Model
{
    /** @use HasFactory<OrderShipmentFactory> */
    use HasFactory;

    protected $fillable = ['order_id', 'courier_integration_id', 'provider', 'external_id', 'tracking_code', 'status', 'raw_response', 'sent_at', 'picked_up_at', 'delivered_at', 'returned_at', 'last_synced_at'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function courierIntegration(): BelongsTo
    {
        return $this->belongsTo(CourierIntegration::class);
    }

    protected function casts(): array
    {
        return ['raw_response' => 'array', 'sent_at' => 'datetime', 'picked_up_at' => 'datetime', 'delivered_at' => 'datetime', 'returned_at' => 'datetime', 'last_synced_at' => 'datetime'];
    }
}
