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
        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique()->index();
            // Content & Design
            $table->longText('content');
            $table->string('featured_image')->nullable(); // For banners
            $table->string('layout')->default('default'); // e.g., 'full-width', 'sidebar'
            // SEO Suite (The "Secret Sauce" for Google)
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();
            // Visibility
            $table->boolean('is_published')->default(false)->index();
            $table->timestamp('published_at')->nullable(); // Better than just a boolean
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_pages');
    }
};
