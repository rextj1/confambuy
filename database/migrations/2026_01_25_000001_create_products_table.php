<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->nullable()->index(); // Typically used for a SKU (Stock Keeping Unit), which is a product identifier like PROD-001.
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('slug')->unique();
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('compare_at_price', 12, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('featured')->default(false)->index(); // Added: For home page highlights
            $table->boolean('taxable')->default(true); // Added: For Naira VAT logic
            $table->timestamp('published_at')->nullable(); // Added: For scheduling launches
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
