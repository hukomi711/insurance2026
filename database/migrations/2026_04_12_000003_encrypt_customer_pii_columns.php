<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Prepare customer_profiles for PII encryption (national_id, phone_number, email).
 *
 * 1. Widen columns to TEXT (encrypted ciphertext is ~200+ chars).
 * 2. Add blind-index hash columns for equality lookups.
 * 3. Move unique constraint from national_id → national_id_hash.
 * 4. Replace phone_number index with phone_number_hash index.
 *
 * After running this migration, execute:
 *   php artisan customers:encrypt-pii
 * to encrypt existing plaintext rows and populate hash columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Step 1: Drop indexes that reference the columns we're widening ──
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropUnique('cp_national_id_unique');
            $table->dropIndex('customer_profiles_phone_number_index');
        });

        // ── Step 2: Widen PII columns to TEXT for encrypted ciphertext ──
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->text('national_id')->nullable()->change();
            $table->text('phone_number')->nullable()->change();
            $table->text('email')->nullable()->change();
        });

        // ── Step 3: Add blind-index hash columns ──
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->string('national_id_hash', 64)->nullable()->after('national_id');
            $table->string('phone_number_hash', 64)->nullable()->after('phone_number');

            $table->unique('national_id_hash', 'cp_national_id_hash_unique');
            $table->index('phone_number_hash', 'cp_phone_number_hash_index');
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropUnique('cp_national_id_hash_unique');
            $table->dropIndex('cp_phone_number_hash_index');
            $table->dropColumn(['national_id_hash', 'phone_number_hash']);
        });

        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->string('national_id', 20)->nullable()->change();
            $table->string('phone_number', 20)->nullable()->change();
            $table->string('email', 255)->nullable()->change();
        });

        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->unique('national_id', 'cp_national_id_unique');
            $table->index('phone_number', 'customer_profiles_phone_number_index');
        });
    }
};
