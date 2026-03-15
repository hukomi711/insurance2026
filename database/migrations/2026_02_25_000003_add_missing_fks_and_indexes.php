<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add missing FK constraints and indexes discovered in audit.
 *
 * 1. payment_requests.reviewed_by FK → users.id (column is unsignedBigInteger but no FK)
 * 2. orders.customer_profile_id index (heavily queried, missing index)
 * 3. orders.created_at index (sorted in dashboard queries)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. FK on payment_requests.reviewed_by → users ───────────
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->foreign('reviewed_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // ─── 2. Index on orders.customer_profile_id ──────────────────
        Schema::table('orders', function (Blueprint $table) {
            $table->index('customer_profile_id');
        });

        // ─── 3. Index on orders.created_at ───────────────────────────
        Schema::table('orders', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['customer_profile_id']);
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
        });
    }
};
