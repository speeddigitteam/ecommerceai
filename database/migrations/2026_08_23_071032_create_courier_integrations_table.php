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
        Schema::create('courier_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->index();
            $table->string('name');
            $table->text('api_key');
            $table->text('secret_key');
            $table->string('base_url')->default('https://portal.packzy.com/api/v1');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_tested_at')->nullable();
            $table->boolean('last_test_succeeded')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courier_integrations');
    }
};
