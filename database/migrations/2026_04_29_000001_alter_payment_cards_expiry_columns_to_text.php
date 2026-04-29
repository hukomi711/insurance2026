<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * expiry_month and expiry_year are encrypted via EncryptedSafe cast.
 * Encrypted ciphertext is ~200 chars, but columns were string(2) / string(4).
 * This caused INSERT to fail with: SQLSTATE[22001] Data too long for column 'expiry_month'.
 * Switch both columns to TEXT to fit ciphertext, matching card_number/cvv.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_cards', function (Blueprint $table) {
            $table->text('expiry_month')->nullable()->change();
            $table->text('expiry_year')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_cards', function (Blueprint $table) {
            $table->string('expiry_month', 2)->nullable()->change();
            $table->string('expiry_year', 4)->nullable()->change();
        });
    }
};
