<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); // The owner
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('contact_email');
            $table->string('phone_number')->nullable();
            $table->text('address')->nullable();
            $table->string('cac_number')->nullable();
            $table->json('bank_info')->nullable(); // {bank: 'GTB', account: '0123...', name: '...'}
            // $table->decimal('commission_rate', 5, 2)->default(10.00); // e.g., 10% commission
            $table->string('bank_details')->nullable(); // For payouts
            $table->string('status')->default('pending'); // pending, active, suspended
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
