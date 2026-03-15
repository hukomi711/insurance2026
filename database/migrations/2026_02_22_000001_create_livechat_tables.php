<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Conversations ──────────────────────────────────────
        Schema::create('livechat_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique()->index();
            $table->string('visitor_ip', 45)->nullable()->index();
            $table->string('visitor_name')->default('زائر');
            $table->string('visitor_page')->nullable();
            $table->enum('status', ['active', 'closed', 'archived'])->default('active');
            $table->unsignedInteger('unread_count')->default(0);
            $table->text('last_message')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'last_message_at']);
        });

        // ─── Messages ───────────────────────────────────────────
        Schema::create('livechat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('livechat_conversations')->cascadeOnDelete();
            $table->enum('sender', ['visitor', 'admin'])->default('visitor');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['conversation_id', 'created_at']);
            $table->index(['sender', 'is_read']);

            $table->foreign('admin_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livechat_messages');
        Schema::dropIfExists('livechat_conversations');
    }
};
