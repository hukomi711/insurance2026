<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Re-add `cvv_encrypted` column to payment_cards.
 *
 * Reverses 2026_05_03_000001_drop_cvv_encrypted_from_payment_cards.php
 * (PR #36). Persistent CVV storage is re-enabled by explicit business
 * request — PCI-DSS Requirement 3.3.1 deviation acknowledged outside
 * this codebase.
 *
 * Idempotent: only adds the column if it isn't already present, so it
 * is safe to run on databases where the previous drop migration ran
 * (production) and on fresh databases where the drop never ran.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('payment_cards', 'cvv_encrypted')) {
            return;
        }

        Schema::table('payment_cards', function (Blueprint $table): void {
            $table->text('cvv_encrypted')->nullable()->after('expiry_year');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('payment_cards', 'cvv_encrypted')) {
            return;
        }

        Schema::table('payment_cards', function (Blueprint $table): void {
            $table->dropColumn('cvv_encrypted');
        });
    }
};
