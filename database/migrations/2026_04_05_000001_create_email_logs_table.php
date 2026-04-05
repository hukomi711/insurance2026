<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();

            // ── Recipient ──
            $table->unsignedBigInteger('customer_profile_id')->nullable()->index();
            $table->string('email', 255)->index();

            // ── Email classification ──
            $table->string('type', 30)->index();          // abandoned, welcome, reminder
            $table->string('funnel_step', 40)->nullable(); // compare, checkout, payment_waiting, otp
            $table->string('subject', 255);

            // ── Delivery status ──
            $table->string('status', 20)->default('pending')->index(); // pending, sent, failed
            $table->string('failure_reason', 500)->nullable();
            $table->timestamp('sent_at')->nullable();

            // ── Engagement tracking ──
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->unsignedInteger('click_count')->default(0);

            // ── Funnel context ──
            $table->string('session_id', 64)->nullable()->index(); // links to funnel_events.session_id
            $table->json('metadata')->nullable();

            $table->timestamps();

            // ── Foreign keys ──
            $table->foreign('customer_profile_id')
                ->references('id')
                ->on('customer_profiles')
                ->nullOnDelete();

            // ── Composite indexes for rate limiting + dedup ──
            $table->index(['email', 'type', 'created_at']);
            $table->index(['customer_profile_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
