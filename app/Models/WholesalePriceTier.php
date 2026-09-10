<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WholesalePriceTier extends Model
{
    /** @var list<string> */
    protected $fillable = ['product_id', 'product_variant_id', 'minimum_quantity', 'unit_price'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2'];
    }
}
