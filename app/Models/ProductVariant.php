<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['product_id', 'sku', 'price', 'sale_price', 'stock_quantity', 'image_path', 'options'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function wholesalePriceTiers(): HasMany
    {
        return $this->hasMany(WholesalePriceTier::class);
    }

    public function getCurrentPriceAttribute(): ?float
    {
        $price = $this->sale_price ?? $this->price;

        return $price === null ? null : (float) $price;
    }

    public function getLabelAttribute(): string
    {
        return collect($this->options ?? [])->map(fn (string $value, string $name): string => "{$name}: {$value}")->implode(', ');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['options' => 'array', 'price' => 'decimal:2', 'sale_price' => 'decimal:2'];
    }
}
