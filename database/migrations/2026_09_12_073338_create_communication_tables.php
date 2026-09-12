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
        Schema::create('communication_providers', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20)->index();
            $table->string('name');
            $table->string('driver', 50);
            $table->text('settings');
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
            $table->unique(['channel', 'name']);
        });

        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20)->index();
            $table->string('name');
            $table->string('subject')->nullable();
            $table->longText('body');
            $table->boolean('is_important')->default(false);
            $table->timestamps();
            $table->unique(['channel', 'name']);
        });

        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('communication_provider_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20)->index();
            $table->string('recipient');
            $table->string('subject')->nullable();
            $table->longText('body');
            $table->string('status', 20)->index();
            $table->text('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('details');
            $table->json('publish_to');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notices');
        Schema::dropIfExists('communication_logs');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('communication_providers');
    }
};
