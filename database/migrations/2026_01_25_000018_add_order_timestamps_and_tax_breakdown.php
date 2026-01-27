<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status')->default('unpaid')->after('status');
            $table->string('payment_method')->nullable()->after('payment_status');
            $table->timestamp('placed_at')->nullable()->after('created_at');
            $table->timestamp('shipped_at')->nullable()->after('placed_at');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
            $table->timestamp('cancelled_at')->nullable()->after('delivered_at');
            $table->decimal('refunded_total', 12, 2)->default(0)->after('grand_total');
            $table->string('customer_ip')->nullable()->after('currency');
            $table->json('tax_breakdown')->nullable()->after('tax_total');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'payment_method', 'placed_at', 'shipped_at', 'delivered_at', 'cancelled_at', 'refunded_total', 'customer_ip', 'tax_breakdown']);
        });
    }
};
