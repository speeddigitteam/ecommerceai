<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->json('delivery_charges')->nullable();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->string('delivery_charge_type')->default('default');
            $table->json('delivery_charges')->nullable();
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_area')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('delivery_area');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['delivery_charge_type', 'delivery_charges']);
        });
        Schema::table('website_settings', function (Blueprint $table) {
            $table->dropColumn('delivery_charges');
        });
    }
};
