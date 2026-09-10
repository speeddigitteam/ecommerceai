<?php

namespace App\Models;

use Database\Factories\WebsiteSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteSetting extends Model
{
    /** @use HasFactory<WebsiteSettingFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'delivery_charges',
        'site_name',
        'business_phone',
        'business_address',
        'website_url',
        'seo_title',
        'meta_description',
        'meta_keywords',
        'logo_path',
        'favicon_path',
        'featured_image_path',
        'footer_content',
        'hero_title',
        'hero_subtitle',
        'hero_product_id',
        'hero_slider_paths',
        'hero_side_image_one_path',
        'hero_side_image_two_path',
        'service_marquee_items',
        'service_marquee_speed',
        'service_marquee_enabled',
        'recaptcha_enabled',
        'recaptcha_site_key',
        'recaptcha_secret_key',
        'homepage_faqs',
        'flash_sale_settings',
        'backup_disk',
        'backup_path_prefix',
        'backup_s3_key',
        'backup_s3_secret',
        'backup_s3_region',
        'backup_s3_bucket',
        'backup_s3_endpoint',
        'backup_s3_use_path_style_endpoint',
        'backup_frequency',
        'backup_last_run_at',
        'ga_tracking_enabled',
        'ga_measurement_id',
        'ga_property_id',
        'ga_service_account_json',
        'search_console_verification',
        'openai_enabled',
        'openai_api_key',
        'openai_model',
        'openai_max_output_tokens',
        'openai_default_language',
        'openai_default_tone',
    ];

    /** @return list<array{question: string, answer: string}> */
    public static function defaultHomepageFaqs(): array
    {
        return [
            ['question' => 'Do you deliver all over Bangladesh?', 'answer' => 'Yes. We provide reliable delivery to addresses throughout Bangladesh. Delivery time and charge depend on the destination.'],
            ['question' => 'How can I place an order?', 'answer' => 'Choose a product, add it to your cart or use direct order, then provide your delivery details and confirm the order.'],
            ['question' => 'What payment methods do you accept?', 'answer' => 'Available payment methods are shown during checkout. Cash on Delivery is available for eligible orders.'],
            ['question' => 'How long does delivery take?', 'answer' => 'Delivery time varies by location and product availability. Our team will confirm the expected timeline after your order is placed.'],
            ['question' => 'Are your products genuine?', 'answer' => 'We carefully source and review our products to provide dependable quality and accurate product information.'],
        ];
    }

    /** @return array{enabled: bool, include_all_sale_products: bool, product_ids: list<int>, eyebrow: string, title: string, description: string, ends_at: null, button_text: string, background_from: string, background_via: string, background_to: string} */
    public static function defaultFlashSaleSettings(): array
    {
        return [
            'enabled' => true,
            'include_all_sale_products' => true,
            'product_ids' => [],
            'eyebrow' => 'Limited time - Up to 70% off',
            'title' => 'Flash Sale',
            'description' => 'Premium picks at all-time-low prices. New items are added regularly, and availability is limited.',
            'ends_at' => null,
            'button_text' => 'View all',
            'background_from' => '#4a0f32',
            'background_via' => '#8f1d61',
            'background_to' => '#e03090',
        ];
    }

    public function heroProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'hero_product_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'delivery_charges' => 'array',
            'hero_slider_paths' => 'array',
            'service_marquee_items' => 'array',
            'service_marquee_enabled' => 'boolean',
            'recaptcha_enabled' => 'boolean',
            'recaptcha_secret_key' => 'encrypted',
            'homepage_faqs' => 'array',
            'flash_sale_settings' => 'array',
            'backup_s3_secret' => 'encrypted',
            'backup_s3_use_path_style_endpoint' => 'boolean',
            'backup_last_run_at' => 'datetime',
            'ga_tracking_enabled' => 'boolean',
            'ga_service_account_json' => 'encrypted',
            'openai_enabled' => 'boolean',
            'openai_api_key' => 'encrypted',
            'openai_max_output_tokens' => 'integer',
        ];
    }
}
