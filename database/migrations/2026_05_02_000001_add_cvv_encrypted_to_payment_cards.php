<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add `cvv_encrypted` to payment_cards.
 *
 * NOTE: This re-introduces persistent CVV storage that was removed by
 * 2026_05_01_000001_drop_cvv_from_payment_cards.php. Storing CVV/CVV2
 * after authorization violates PCI-DSS Requirement 3.3.1. Enabled per
 * explicit business request — risk acknowledged outside this codebase.
 *
 * Storage uses the EncryptedSafe cast (Laravel encrypted-string with key
 * rotation support) on a TEXT column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_cards', function (Blueprint $table): void {
            $table->text('cvv_encrypted')->nullable()->after('expiry_year');
        });
    }

    public function down(): void
    {
        Schema::table('payment_cards', function (Blueprint $table): void {
            $table->dropColumn('cvv_encrypted');
        });
    }
};
