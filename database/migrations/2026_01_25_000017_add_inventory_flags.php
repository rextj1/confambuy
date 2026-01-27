<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->integer('low_stock_threshold')->default(5)->after('reserved');
            $table->boolean('allow_backorder')->default(false)->after('low_stock_threshold');
            $table->string('stock_status')->default('in_stock')->after('allow_backorder');
        });
    }

    public function down(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            $table->dropColumn(['low_stock_threshold', 'allow_backorder', 'stock_status']);
        });
    }
};
