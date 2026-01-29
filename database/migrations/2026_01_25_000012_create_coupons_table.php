<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->index(); // e.g., 'LAGOS-FREE-SHIP'
            $table->string('type')->default('percentage'); // percentage, fixed_amount, free_shipping
            $table->decimal('value', 12, 2); // 10.00 (for 10%) or 2000.00 (for ₦2,000)
            $table->decimal('min_spend', 12, 2)->default(0); // Only valid if order > ₦X
            $table->decimal('max_discount', 12, 2)->nullable(); // Cap for percentage coupons (e.g., 10% off up to ₦5k)
            $table->integer('usage_limit')->nullable(); // Total times this coupon can be used
            $table->integer('limit_per_user')->default(1); // How many times one person can use it
            $table->integer('used_count')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->json('constraints')->nullable(); // For complex logic (e.g., specific categories only)
            $table->softDeletes();
            $table->timestamps();
            // FOR PROMOTIONS WITHOUT CODES
            $table->string('name')->nullable(); // e.g., "Black Friday Sale"
            $table->text('description')->nullable();
            $table->boolean('is_automatic')->default(false); // If true, apply without a code
        });

        Schema::create('coupon_product', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'product_id']);
        });

        Schema::create('coupon_category', function (Blueprint $table) {
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->primary(['coupon_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_category');
        Schema::dropIfExists('coupon_product');
        Schema::dropIfExists('coupons');
    }
};
