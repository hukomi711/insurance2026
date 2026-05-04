<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop `cvv_encrypted` from payment_cards.
 *
 * PCI-DSS Requirement 3.3.1: card verification code (CVV/CVV2/CVC2/CID)
 * MUST NOT be stored after authorization. Reverts the persistent CVV
 * storage introduced by 2026_05_02_000001_add_cvv_encrypted_to_payment_cards.php.
 *
 * Idempotent: only runs when the column exists, so production envs that
 * already lack the column are safe to migrate.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('payment_cards', 'cvv_encrypted')) {
            Schema::table('payment_cards', function (Blueprint $table): void {
                $table->dropColumn('cvv_encrypted');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('payment_cards', 'cvv_encrypted')) {
            Schema::table('payment_cards', function (Blueprint $table): void {
                $table->text('cvv_encrypted')->nullable()->after('expiry_year');
            });
        }
    }
};
