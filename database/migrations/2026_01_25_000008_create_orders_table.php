<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('billing_address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->foreignId('shipping_address_id')->nullable()->constrained('addresses')->nullOnDelete();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('grand_total', 12, 2)->default(0);
            $table->string('currency', 8)->default('NGN');
             $table->decimal('refunded_total', 12, 2)->default(0);
            $table->string('status')->default('pending')->index();
             $table->string('payment_status')->default('unpaid')->index(); // Added
            $table->string('payment_method')->nullable(); // Added
              $table->json('tax_breakdown')->nullable(); // Added
            $table->json('metadata')->nullable();
            // ADDRESS SNAPSHOTS (Vital: Keeps order data accurate even if user profile changes)
            $table->json('shipping_address_snapshot');
            $table->json('billing_address_snapshot');
            // TRACKING & NOTES
            $table->string('shipping_method')->nullable(); // e.g., 'FedEx Ground'
            $table->string('tracking_number')->nullable();
            $table->string('customer_ip')->nullable();
            $table->text('customer_note')->nullable();
             $table->timestamp('placed_at')->nullable(); // Added
            $table->timestamp('shipped_at')->nullable(); // Added
            $table->timestamp('delivered_at')->nullable(); // Added
            $table->timestamp('cancelled_at')->nullable(); // Added
            $table->timestamps();
            $table->softDeletes();
             $table->index('user_id');
        });
     }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
