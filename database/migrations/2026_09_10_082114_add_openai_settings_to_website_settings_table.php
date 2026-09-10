<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table) {
            $table->boolean('openai_enabled')->default(false);
            $table->text('openai_api_key')->nullable();
            $table->string('openai_model')->default('gpt-5.2');
            $table->unsignedSmallInteger('openai_max_output_tokens')->default(2500);
            $table->string('openai_default_language', 20)->default('Bangla');
            $table->string('openai_default_tone', 30)->default('Professional');
        });
    }

    public function down(): void
    {
        Schema::table('website_settings', fn (Blueprint $table) => $table->dropColumn(['openai_enabled', 'openai_api_key', 'openai_model', 'openai_max_output_tokens', 'openai_default_language', 'openai_default_tone']));
    }
};
