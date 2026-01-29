<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_sku_id')->nullable()->constrained('product_skus')->nullOnDelete();
            $table->integer('quantity')->default(1);
            $table->string('parcel_number')->nullable()->index(); // Indexed for quick box lookups;
            $table->timestamps();
            $table->index(['shipment_id', 'order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
    }
};
