<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete(); // For "Verified Purchase" badge
            $table->tinyInteger('rating')->unsigned()->default(5); // 1 to 5
            $table->string('title')->nullable(); // string is usually enough for a title
            $table->text('body')->nullable();
            $table->json('images')->nullable(); // Support for customer-uploaded photos
            $table->boolean('is_approved')->default(false)->index();
            $table->integer('helpful_count')->default(0);
            $table->string('ip_address')->nullable();
           $table->timestamp('approved_at')->nullable();
            $table->boolean('is_featured')->default(false); // Highlight the best reviews at the top
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
