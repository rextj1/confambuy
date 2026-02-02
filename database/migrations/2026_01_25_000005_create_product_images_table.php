<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_sku_id')->nullable()->constrained('product_skus')->nullOnDelete();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->integer('position')->default(0)->index();
            $table->boolean('is_featured')->default(false); // Quick way to find the "Main" image
            $table->timestamps();
            $table->index('product_id');
            $table->index('product_sku_id');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
