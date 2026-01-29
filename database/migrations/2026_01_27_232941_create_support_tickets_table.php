<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. The Main Ticket Table (The "Issue")
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number')->unique()->index(); // e.g., TKT-88291
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('category')->default('general')->index();; // shipping, billing, product, technical
            $table->string('subject');
            $table->text('description'); // The original message/problem
            $table->string('status')->default('open')->index(); // open, pending, resolved, closed
            $table->string('priority')->default('medium')->index(); // low, medium, high, urgent
            $table->timestamp('last_reply_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete(); // Admin staff ID
            $table->timestamps();
            $table->softDeletes();
        });
        // 2. The Messages Table (The "Conversation")
        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Sender of this message
            $table->text('message');
            $table->json('attachments')->nullable(); // Store paths to images/files
            // Secret "Admin Only" notes
            $table->boolean('is_internal')->default(false)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
