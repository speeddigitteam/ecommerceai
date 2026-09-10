<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * Stock quantity stored for digital products, which are never limited by physical inventory.
     */
    public const UNLIMITED_STOCK = 100000;

    /** @var list<string> */
    protected $fillable = ['delivery_charge_type', 'delivery_charges', 'brand_id', 'unit_id', 'type', 'title', 'slug', 'sku', 'description', 'short_description', 'specifications', 'questions', 'price', 'sale_price', 'stock_quantity', 'status', 'visibility', 'published_at', 'featured_image_path', 'video_path', 'gallery_paths', 'digital_file_path', 'digital_file_name', 'tags', 'focus_keyword', 'seo_title', 'meta_description'];

    public function isDigital(): bool
    {
        return $this->type === 'digital';
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function getCategoryAttribute(): ?Category
    {
        return $this->categories->first();
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function wholesalePriceTiers(): HasMany
    {
        return $this->hasMany(WholesalePriceTier::class)->orderBy('minimum_quantity');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function getCurrentPriceAttribute(): ?float
    {
        $price = $this->sale_price ?? $this->price;

        return $price === null ? null : (float) $price;
    }

    /**
     * Recalculate price, sale price, and stock from the current variants and persist them,
     * so catalog listing, filtering, and stock checks keep reading plain product columns.
     */
    public function syncAggregatesFromVariants(): void
    {
        $variants = $this->variants()->get();
        if ($variants->isEmpty()) {
            return;
        }

        /** @var Collection<int, ProductVariant> $pricedVariants */
        $pricedVariants = $variants->filter(fn (ProductVariant $variant): bool => $variant->price !== null);
        /** @var Collection<int, ProductVariant> $saleVariants */
        $saleVariants = $variants->filter(fn (ProductVariant $variant): bool => $variant->sale_price !== null);

        $this->forceFill([
            'price' => $pricedVariants->isNotEmpty() ? $pricedVariants->min('price') : $this->price,
            'sale_price' => $saleVariants->isNotEmpty() ? $saleVariants->min('sale_price') : null,
            'stock_quantity' => $variants->sum('stock_quantity'),
        ])->save();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'delivery_charges' => 'array', 'gallery_paths' => 'array', 'tags' => 'array', 'specifications' => 'array', 'questions' => 'array', 'published_at' => 'datetime', 'price' => 'decimal:2', 'sale_price' => 'decimal:2'];
    }
}
