<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('website_settings')) {
            return;
        }

        Schema::create('website_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->string('business_phone', 30)->nullable();
            $table->text('business_address')->nullable();
            $table->string('website_url')->nullable();
            $table->string('seo_title');
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('featured_image_path')->nullable();
            $table->string('hero_title')->nullable();
            $table->string('hero_subtitle')->nullable();
            $table->foreignId('hero_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->json('hero_slider_paths')->nullable();
            $table->string('hero_side_image_one_path')->nullable();
            $table->string('hero_side_image_two_path')->nullable();
            $table->json('service_marquee_items')->nullable();
            $table->unsignedSmallInteger('service_marquee_speed')->default(28);
            $table->boolean('service_marquee_enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_settings');
    }
};
