<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Database Cleanup Migration — إصلاح شامل لقاعدة البيانات
 *
 * 1. حذف 4 جداول مهجورة (legacy) تم استبدالها بجداول جديدة
 * 2. إضافة عمود assigned_admin_id لربط العميل بالمشرف
 * 3. حذف الفهارس المكررة (redundant) من otp_codes و payment_cards
 * 4. إصلاح نوع عمود reviewed_by في payment_requests (string → unsignedBigInteger)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. Drop orphan legacy tables ────────────────────────────
        // These were the original tables from 2026_02_14, superseded by:
        //   tracked_customers → customer_profiles
        //   customer_otps → otp_codes
        //   customer_payment_cards → payment_cards
        //   customer_phone_verifications → phone_verifications
        Schema::dropIfExists('customer_phone_verifications');
        Schema::dropIfExists('customer_payment_cards');
        Schema::dropIfExists('customer_otps');
        Schema::dropIfExists('tracked_customers');

        // ─── 2. Add assigned_admin_id FK to customer_profiles ────────
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->foreignId('assigned_admin_id')
                ->nullable()
                ->after('notes')
                ->constrained('users')
                ->nullOnDelete();
        });

        // ─── 3. Drop redundant duplicate indexes ────────────────────
        // otp_codes: idx_otp_customer_profile is redundant because
        //   the composite index (customer_profile_id, type, status)
        //   already covers customer_profile_id as leftmost prefix
        try {
            Schema::table('otp_codes', function (Blueprint $table) {
                $table->dropIndex('idx_otp_customer_profile');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Index may not exist (e.g. SQLite :memory: in tests)
        }

        // payment_cards: idx_cards_customer_profile is redundant because
        //   the composite index (customer_profile_id, status) already covers it
        try {
            Schema::table('payment_cards', function (Blueprint $table) {
                $table->dropIndex('idx_cards_customer_profile');
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Index may not exist (e.g. SQLite :memory: in tests)
        }

        // ─── 4. Fix payment_requests.reviewed_by column type ─────────
        // Currently string, should be unsignedBigInteger for FK compatibility
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('reviewed_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Revert payment_requests.reviewed_by
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->string('reviewed_by')->nullable()->change();
        });

        // Re-add redundant indexes
        Schema::table('payment_cards', function (Blueprint $table) {
            $table->index('customer_profile_id', 'idx_cards_customer_profile');
        });

        Schema::table('otp_codes', function (Blueprint $table) {
            $table->index('customer_profile_id', 'idx_otp_customer_profile');
        });

        // Remove assigned_admin_id
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_admin_id');
        });

        // Re-create legacy tables (minimal schema for rollback)
        Schema::create('tracked_customers', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->timestamps();
        });

        Schema::create('customer_otps', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('code');
            $table->timestamps();
        });

        Schema::create('customer_payment_cards', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('card_number');
            $table->timestamps();
        });

        Schema::create('customer_phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('phone_number');
            $table->timestamps();
        });
    }
};
