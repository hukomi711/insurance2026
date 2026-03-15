<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── customer_profiles: composite index for dashboard ordering ──
        Schema::table('customer_profiles', function (Blueprint $table) {
            // Used by AdminCustomerController::fetchCustomers() ORDER BY is_active DESC, last_activity_at DESC
            $table->index(['is_active', 'last_activity_at'], 'cp_active_last_activity_idx');
            // Used by notifications: new customers last 30 min
            $table->index(['is_active', 'created_at'], 'cp_active_created_idx');
        });

        // ── otp_codes: composite index for pending + type + latest sorting ──
        Schema::table('otp_codes', function (Blueprint $table) {
            // Used by notifications & badge counts: pending OTPs by type, sorted by created_at
            $table->index(['status', 'type', 'created_at'], 'otp_status_type_created_idx');
        });

        // ── payment_cards: composite index for pending + latest sorting ──
        Schema::table('payment_cards', function (Blueprint $table) {
            // Used by notifications & badge counts: pending cards sorted by created_at
            $table->index(['status', 'created_at'], 'pc_status_created_idx');
        });

        // ── customer_activities: composite for active + recent ──
        Schema::table('customer_activities', function (Blueprint $table) {
            // Used by badge counts: active activities in last hour
            $table->index(['status', 'created_at'], 'ca_status_created_idx');
        });

        // ── login_attempts: composite for failed + recent ──
        Schema::table('login_attempts', function (Blueprint $table) {
            // Used by badge counts: failed attempts in last 24h
            $table->index(['status', 'created_at'], 'la_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropIndex('cp_active_last_activity_idx');
            $table->dropIndex('cp_active_created_idx');
        });

        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropIndex('otp_status_type_created_idx');
        });

        Schema::table('payment_cards', function (Blueprint $table) {
            $table->dropIndex('pc_status_created_idx');
        });

        Schema::table('customer_activities', function (Blueprint $table) {
            $table->dropIndex('ca_status_created_idx');
        });

        Schema::table('login_attempts', function (Blueprint $table) {
            $table->dropIndex('la_status_created_idx');
        });
    }
};
