<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->timestamp('approved_at')->nullable()->after('approved');
            $table->integer('helpful_count')->default(0)->after('approved_at');
            $table->string('ip_address')->nullable()->after('helpful_count');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['approved_at', 'helpful_count', 'ip_address']);
        });
    }
};
