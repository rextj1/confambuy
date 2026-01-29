<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_sku_id')->constrained('product_skus')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('reserved')->default(0);
            $table->string('location')->nullable();
            $table->timestamps();
            $table->integer('low_stock_threshold')->default(5); // Trigger for "Running Low" notifications
            $table->boolean('allow_backorder')->default(false); // Can customers buy if stock is 0?
            $table->string('stock_status')->default('in_stock')->index();
            $table->index('product_sku_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
