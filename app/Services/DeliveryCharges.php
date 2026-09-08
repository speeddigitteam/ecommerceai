<?php

namespace App\Services;

use App\Models\Product;
use App\Models\WebsiteSetting;

class DeliveryCharges
{
    public const AREAS = ['dhaka_city' => 'Dhaka City', 'dhaka_outside' => 'Outside Dhaka City', 'outside_dhaka' => 'Outside Dhaka District'];

    public const DEFAULTS = ['dhaka_city' => 50, 'dhaka_outside' => 80, 'outside_dhaka' => 100];

    /** @param iterable<Product> $products
     * @return array<string, float>
     */
    public function rates(iterable $products): array
    {
        $defaults = WebsiteSetting::query()->value('delivery_charges') ?? self::DEFAULTS;
        $rates = array_fill_keys(array_keys(self::AREAS), 0.0);
        foreach ($products as $product) {
            if ($product->isDigital() || $product->delivery_charge_type === 'free') {
                continue;
            }
            foreach ($rates as $area => $rate) {
                $charge = $product->delivery_charge_type === 'custom'
                    ? ($product->delivery_charges[$area] ?? $defaults[$area] ?? self::DEFAULTS[$area])
                    : ($defaults[$area] ?? self::DEFAULTS[$area]);
                $rates[$area] = max($rate, (float) $charge);
            }
        }

        return $rates;
    }
}
