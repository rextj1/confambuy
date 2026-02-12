<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('product_images');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('product_images', function ($table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_sku_id')->nullable()->constrained('product_skus')->nullOnDelete();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->integer('position')->default(0)->index();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->index('product_id');
            $table->index('product_sku_id');
            $table->softDeletes();
        });
    }
};
