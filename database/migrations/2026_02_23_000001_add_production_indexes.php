<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing indexes for production performance under 200+ concurrent users.
 *
 * - orders.status, orders.payment_status: Filtered queries in admin dashboard
 * - payment_cards.reviewed_by FK: Referential integrity for admin reviewer
 * - customer_profiles composite: (ip_address, session_id) for session-token identification
 * - login_attempts.ip_address + created_at: Brute-force lockout lookups
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index('status');
            $table->index('payment_status');
        });

        Schema::table('payment_cards', function (Blueprint $table) {
            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->unique(['ip_address', 'session_id'], 'cp_ip_session_unique');
        });

        Schema::table('login_attempts', function (Blueprint $table) {
            $table->index(['ip_address', 'status', 'created_at'], 'la_lockout_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_status']);
        });

        Schema::table('payment_cards', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
        });

        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropUnique('cp_ip_session_unique');
        });

        Schema::table('login_attempts', function (Blueprint $table) {
            $table->dropIndex('la_lockout_lookup');
        });
    }
};
