<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Attributes table
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('string');
            $table->integer('position')->default(0); // Optional: for ordering attributes
            $table->timestamps();
        });

        // Attribute values table
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('label')->nullable();
            $table->integer('position')->default(0); // For sorting values (Small, Medium, Large)
            $table->unique(['attribute_id', 'value']); // Prevent duplicate values per attribute
            $table->timestamps();
        });

        // Pivot table linking attribute values to product SKUs
        Schema::create('attribute_value_product_sku', function (Blueprint $table) {
            $table->foreignId('attribute_value_id')->constrained('attribute_values')->cascadeOnDelete();
            $table->foreignId('product_sku_id')->constrained('product_skus')->cascadeOnDelete();
            $table->primary(['attribute_value_id', 'product_sku_id']); // Composite PK
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_value_product_sku');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
    }
};
