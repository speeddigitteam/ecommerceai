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
        Schema::table('products', function (Blueprint $table) {
            $table->string('type')->default('physical')->after('unit_id')->index();
            $table->string('digital_file_path')->nullable()->after('gallery_paths');
            $table->string('digital_file_name')->nullable()->after('digital_file_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['type', 'digital_file_path', 'digital_file_name']);
        });
    }
};
