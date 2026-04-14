<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Drop orphaned tables that have no Model, no Controller usage,
 * and were superseded by payment_cards and otp_codes respectively.
 *
 * payment_requests    → replaced by payment_cards (since 2026-02-20)
 * phone_verifications → replaced by otp_codes type='phone'/'phone_verification' (since 2026-02-22)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('phone_verifications');
        Schema::dropIfExists('payment_requests');
    }

    public function down(): void
    {
        // Intentionally empty — these tables were unused legacy artifacts.
        // If needed, recreate from 2026_02_20_000002 and 2026_02_20_000003 migrations.
    }
};
