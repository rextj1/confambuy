<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway'); // stripe, paypal, razorpay
            $table->string('gateway_id')->nullable()->index(); // The transaction ID from the provider
            $table->string('payment_method')->nullable(); // card, bank_transfer, wallet
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('fee', 12, 2)->default(0); // Optional: track gateway fees
            $table->string('currency', 3)->default('NGN'); // ISO 4217 standard is 3 chars
            $table->string('status')->default('pending')->index();
            $table->json('payload')->nullable();
            $table->decimal('refunded_amount', 12, 2)->default(0);
            $table->json('method_details')->nullable();
            $table->boolean('captured')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
