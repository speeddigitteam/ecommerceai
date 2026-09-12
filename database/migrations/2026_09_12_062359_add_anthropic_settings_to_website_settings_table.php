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
        Schema::table('website_settings', function (Blueprint $table) {
            $table->boolean('anthropic_enabled')->default(false)->after('openai_enabled');
            $table->text('anthropic_api_key')->nullable()->after('anthropic_enabled');
            $table->string('anthropic_model')->nullable()->after('anthropic_api_key');
            $table->unsignedSmallInteger('anthropic_max_output_tokens')->default(2500)->after('anthropic_model');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->dropColumn([
                'anthropic_enabled',
                'anthropic_api_key',
                'anthropic_model',
                'anthropic_max_output_tokens',
            ]);
        });
    }
};
