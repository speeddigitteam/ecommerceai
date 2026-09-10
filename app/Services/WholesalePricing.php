<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

class WholesalePricing
{
    /** @return array{unit_price: float, pricing_type: string, minimum_quantity: int|null} */
    public function resolve(Product $product, ?ProductVariant $variant, int $quantity, ?User $customer): array
    {
        $retailPrice = $variant?->current_price ?? $product->current_price;
        $tiers = $variant?->wholesalePriceTiers;
        if ($tiers === null || $tiers->isEmpty()) {
            $tiers = $product->wholesalePriceTiers->whereNull('product_variant_id');
        }

        if (! $customer?->isApprovedWholesaler() || $tiers->isEmpty()) {
            return ['unit_price' => (float) $retailPrice, 'pricing_type' => 'retail', 'minimum_quantity' => null];
        }

        $tier = $tiers->where('minimum_quantity', '<=', $quantity)->sortByDesc('minimum_quantity')->first();
        $minimumQuantity = (int) $tiers->min('minimum_quantity');

        return $tier
            ? ['unit_price' => (float) $tier->unit_price, 'pricing_type' => 'wholesale', 'minimum_quantity' => $minimumQuantity]
            : ['unit_price' => (float) $retailPrice, 'pricing_type' => 'retail', 'minimum_quantity' => $minimumQuantity];
    }
}
