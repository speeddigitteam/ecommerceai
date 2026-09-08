<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table): void {
            $table->boolean('ga_tracking_enabled')->default(false);
            $table->string('ga_measurement_id')->nullable();
            $table->string('ga_property_id')->nullable();
            $table->text('ga_service_account_json')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'ga_tracking_enabled',
                'ga_measurement_id',
                'ga_property_id',
                'ga_service_account_json',
            ]);
        });
    }
};
