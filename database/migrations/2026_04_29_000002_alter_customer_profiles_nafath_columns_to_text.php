<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * nafath_username and nafath_password are encrypted via EncryptedSafe cast.
 * Encrypted ciphertext is ~200+ chars but columns were VARCHAR(255).
 * For ID + password this still overflowed (long base64 wrapper). Switch to TEXT
 * to match other encrypted columns (national_id, phone_number, email).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->text('nafath_username')->nullable()->change();
            $table->text('nafath_password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->string('nafath_username')->nullable()->change();
            $table->string('nafath_password')->nullable()->change();
        });
    }
};
