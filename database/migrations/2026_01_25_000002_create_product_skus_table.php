<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_skus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique()->index(); // Added unique index
            $table->string('title')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('cost', 12, 2)->nullable(); // Added: Your purchase cost
            // Physical Specs (For Shipping/Logistics)
            $table->decimal('weight', 8, 3)->nullable();
            $table->decimal('length', 8, 3)->nullable();
            $table->decimal('width', 8, 3)->nullable();
            $table->decimal('height', 8, 3)->nullable();
            $table->json('attributes')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->boolean('manage_stock')->default(true); // Added: Toggle inventory counting
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_skus');
    }
};
