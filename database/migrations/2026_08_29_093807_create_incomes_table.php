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
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->date('entry_date')->index();
            $table->text('remarks')->nullable();
            $table->decimal('total_amount', 14, 2);
            $table->timestamps();
        });

        Schema::create('income_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('income_id')->constrained()->cascadeOnDelete();
            $table->foreignId('income_category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category_name');
            $table->decimal('amount', 14, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('income_items');
        Schema::dropIfExists('incomes');
    }
};
