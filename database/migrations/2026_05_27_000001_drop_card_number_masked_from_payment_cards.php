<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop the `card_number_masked` column from `payment_cards` table.
 *
 * This column was populated during card submission but is not used by the
 * application. The full `card_number` (encrypted via EncryptedSafe cast)
 * is always available to admins via the card display attribute, and the
 * frontend client library (Stripe, Mojaz, etc.) does not need a masked
 * version in the database.
 *
 * Removing this column:
 * - Reduces storage footprint
 * - Eliminates a stale masking implementation
 * - Simplifies the PaymentCard schema
 * - Clarifies that PCI-DSS compliant display is done in-application,
 *   not via a pre-computed database field
 *
 * Idempotent: only drops the column if it exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('payment_cards', 'card_number_masked')) {
            Schema::table('payment_cards', function (Blueprint $table): void {
                $table->dropColumn('card_number_masked');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('payment_cards', 'card_number_masked')) {
            Schema::table('payment_cards', function (Blueprint $table): void {
                $table->string('card_number_masked')->nullable()->after('card_number');
            });
        }
    }
};
