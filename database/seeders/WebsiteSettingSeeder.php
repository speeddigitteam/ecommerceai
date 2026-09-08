<?php

namespace Database\Seeders;

use App\Models\WebsiteSetting;
use Illuminate\Database\Seeder;

class WebsiteSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        WebsiteSetting::query()->firstOrCreate([], [
            'site_name' => config('app.name', 'Shopwise'),
            'seo_title' => 'Shopwise - Smart ecommerce management',
            'meta_description' => 'Manage products, categories, brands, units, and orders from Shopwise.',
            'meta_keywords' => 'ecommerce, products, categories, orders, Shopwise',
        ]);
    }
}
