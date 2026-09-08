<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_settings', function (Blueprint $table): void {
            $table->string('backup_disk')->default('local');
            $table->string('backup_path_prefix')->nullable();
            $table->string('backup_s3_key')->nullable();
            $table->text('backup_s3_secret')->nullable();
            $table->string('backup_s3_region')->nullable();
            $table->string('backup_s3_bucket')->nullable();
            $table->string('backup_s3_endpoint')->nullable();
            $table->boolean('backup_s3_use_path_style_endpoint')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('website_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'backup_disk',
                'backup_path_prefix',
                'backup_s3_key',
                'backup_s3_secret',
                'backup_s3_region',
                'backup_s3_bucket',
                'backup_s3_endpoint',
                'backup_s3_use_path_style_endpoint',
            ]);
        });
    }
};
