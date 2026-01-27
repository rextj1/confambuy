<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_skus', function (Blueprint $table) {
            $table->decimal('length', 8, 3)->nullable()->after('weight');
            $table->decimal('width', 8, 3)->nullable()->after('length');
            $table->decimal('height', 8, 3)->nullable()->after('width');
            $table->string('barcode')->nullable()->after('attributes');
            $table->decimal('cost', 12, 2)->nullable()->after('price');
            $table->boolean('manage_stock')->default(true)->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('product_skus', function (Blueprint $table) {
            $table->dropColumn(['length', 'width', 'height', 'barcode', 'cost', 'manage_stock']);
        });
    }
};
