<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('type')->default('physical')->after('slug');
            $table->boolean('taxable')->default(true)->after('active');
            $table->timestamp('published_at')->nullable()->after('metadata');
            $table->boolean('featured')->default(false)->after('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['type', 'taxable', 'published_at', 'featured']);
        });
    }
};
