<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 0 — OTP Security Columns
 *
 * Adds lockout tracking to customer_profiles and hash storage to otp_codes.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Customer lockout columns ────────────────────────────
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->unsignedSmallInteger('otp_fail_count')->default(0)->after('is_active');
            $table->timestamp('otp_locked_until')->nullable()->after('otp_fail_count');
        });

        // ── OTP code hash column ────────────────────────────────
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('code_hash', 64)->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropColumn(['otp_fail_count', 'otp_locked_until']);
        });

        Schema::table('otp_codes', function (Blueprint $table) {
            $table->dropColumn('code_hash');
        });
    }
};
