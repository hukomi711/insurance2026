<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('customer_ip', 45)->nullable();
            $table->string('current_step', 50)->default('motorapp');
            $table->unsignedTinyInteger('step_number')->default(1);
            $table->string('insurance_type', 50)->nullable();

            // Form data snapshot (JSON blobs per step)
            $table->json('vehicle_data')->nullable();
            $table->json('personal_data')->nullable();
            $table->json('comparison_data')->nullable();

            // Status tracking
            $table->enum('status', ['active', 'completed', 'abandoned', 'expired'])->default('active');
            $table->unsignedTinyInteger('completion_percentage')->default(0);

            // Device / browser info
            $table->string('device_type', 30)->nullable();
            $table->string('device_browser', 50)->nullable();
            $table->string('user_agent')->nullable();

            // Timing
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('last_heartbeat_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('abandoned_at')->nullable();

            // Duration tracking (seconds)
            $table->unsignedInteger('total_duration_seconds')->default(0);

            // Referral / UTM
            $table->string('referrer_url')->nullable();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 100)->nullable();

            $table->timestamps();

            // Indexes
            $table->index('customer_ip');
            $table->index('status');
            $table->index('current_step');
            $table->index('last_heartbeat_at');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_sessions');
    }
};
