<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funnel_events', function (Blueprint $table) {
            $table->id();

            // ── Event identity ──
            $table->string('event_name', 60)->index();       // e.g. compare_viewed, otp_requested
            $table->string('session_id', 64)->index();       // customer_session_token (UUID)
            $table->unsignedBigInteger('customer_profile_id')->nullable()->index();
            $table->uuid('quote_uuid')->nullable()->index();  // quote session UUID

            // ── Funnel position ──
            $table->string('step_name', 40)->nullable();      // compare, checkout, otp, card_pin, payment_waiting, confirmation
            $table->unsignedTinyInteger('step_order')->nullable(); // 0-based ordinal
            $table->string('previous_step', 40)->nullable();  // step the user came from

            // ── Context ──
            $table->string('device_type', 10)->nullable();    // mobile | desktop
            $table->string('source', 60)->nullable();         // utm_source
            $table->string('campaign', 100)->nullable();      // utm_campaign
            $table->string('country', 4)->nullable();         // ISO 3166-1 alpha-2
            $table->boolean('is_returning_user')->default(false);
            $table->unsignedInteger('elapsed_seconds')->nullable(); // seconds since funnel start

            // ── Flexible metadata ──
            $table->json('metadata')->nullable();             // reason, otp_id, card_id, etc.

            // ── Server-enriched ──
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();

            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();

            // ── Composite indexes for reporting ──
            $table->index(['session_id', 'step_order']);
            $table->index(['event_name', 'occurred_at']);
            $table->index(['step_name', 'occurred_at']);

            // ── Foreign key ──
            $table->foreign('customer_profile_id')
                ->references('id')
                ->on('customer_profiles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('funnel_events');
    }
};
