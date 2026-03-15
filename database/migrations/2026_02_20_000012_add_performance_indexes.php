<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add composite indexes for common admin dashboard queries.
 * These speed up the most frequent WHERE/ORDER BY patterns.
 */
return new class extends Migration
{
    public function up(): void
    {
        // customer_profiles — admin dashboard sorts active customers by last activity
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->index(['is_active', 'last_activity_at'], 'idx_profiles_active_activity');
            // created_at index for "recent customers" notification query
            $table->index('created_at', 'idx_profiles_created_at');
        });

        // otp_codes — filtered by status + type on notifications & admin views
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->index(['status', 'type'], 'idx_otp_status_type');
            $table->index('customer_profile_id', 'idx_otp_customer_profile');
        });

        // payment_cards — filtered by status in admin dashboard
        Schema::table('payment_cards', function (Blueprint $table) {
            $table->index('status', 'idx_cards_status');
            $table->index('customer_profile_id', 'idx_cards_customer_profile');
        });

        // user_activities — filtered by user + action for audit
        Schema::table('user_activities', function (Blueprint $table) {
            $table->index(['user_id', 'action'], 'idx_activities_user_action');
        });

        // quote_sessions — filtered by status + IP for admin views
        Schema::table('quote_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('quote_sessions', 'customer_profile_id')) {
                $table->index('customer_profile_id', 'idx_sessions_customer_profile');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropIndex('idx_profiles_active_activity');
            $table->dropIndex('idx_profiles_created_at');
        });

        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropIndex('idx_otp_status_type');
            $table->dropIndex('idx_otp_customer_profile');
        });

        Schema::table('payment_cards', function (Blueprint $table) {
            $table->dropIndex('idx_cards_status');
            $table->dropIndex('idx_cards_customer_profile');
        });

        Schema::table('user_activities', function (Blueprint $table) {
            $table->dropIndex('idx_activities_user_action');
        });

        Schema::table('quote_sessions', function (Blueprint $table) {
            $table->dropIndex('idx_sessions_customer_profile');
        });
    }
};
