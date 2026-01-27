<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('sku')->nullable()->after('product_sku_id');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('total');
            $table->decimal('unit_cost', 12, 2)->nullable()->after('unit_price');
            $table->json('sku_snapshot')->nullable()->after('metadata');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['sku', 'tax_amount', 'unit_cost', 'sku_snapshot']);
        });
    }
};
