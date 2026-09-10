<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wholesale_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('minimum_quantity');
            $table->decimal('unit_price', 12, 2);
            $table->timestamps();
            $table->unique(['product_id', 'product_variant_id', 'minimum_quantity'], 'wholesale_tier_quantity_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesale_price_tiers');
    }
};
