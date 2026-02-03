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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., '12-12 Mega Sale'
            $table->string('slug')->unique();
            // Type of Promotion
            $table->string('type')->comment('percentage, fixed_amount, buy_x_get_y');
            $table->decimal('value', 10, 2);
            // API Display
            $table->string('banner_url')->nullable(); // For the frontend slider
            $table->string('description')->nullable();
            // Constraints
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            // Priority: If a product has two promotions, which one wins?
            $table->integer('priority')->default(0);
            $table->timestamps();
            $table->softDeletes();
           

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
