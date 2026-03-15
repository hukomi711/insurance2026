<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add 'stc_otp' and 'phone_verification' to the otp_codes.type enum/check.
 *
 * Full allowed set after migration:
 *   otp, pin, phone, email, stc_verification, stc_otp, phone_verification
 */
return new class extends Migration
{
    /** All allowed type values after this migration. */
    private const NEW_TYPES = ['otp', 'pin', 'phone', 'email', 'stc_verification', 'stc_otp', 'phone_verification'];

    /** Type values before this migration (for rollback). */
    private const OLD_TYPES = ['otp', 'pin', 'phone', 'email', 'stc_verification'];

    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildForSqlite(self::NEW_TYPES);
        } else {
            $enum = implode(',', array_map(fn ($t) => "'$t'", self::NEW_TYPES));
            DB::statement("ALTER TABLE otp_codes MODIFY COLUMN `type` ENUM($enum) NOT NULL DEFAULT 'otp'");
        }
    }

    public function down(): void
    {
        // Convert new types back to closest legacy equivalents before shrinking enum
        DB::table('otp_codes')->where('type', 'stc_otp')->update(['type' => 'stc_verification']);
        DB::table('otp_codes')->where('type', 'phone_verification')->update(['type' => 'phone']);

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildForSqlite(self::OLD_TYPES);
        } else {
            $enum = implode(',', array_map(fn ($t) => "'$t'", self::OLD_TYPES));
            DB::statement("ALTER TABLE otp_codes MODIFY COLUMN `type` ENUM($enum) NOT NULL DEFAULT 'otp'");
        }
    }

    /**
     * SQLite doesn't support ALTER COLUMN — recreate the table with the new enum values.
     */
    private function rebuildForSqlite(array $types): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        // Drop indexes that reference the table (avoids rename conflicts)
        try {
            DB::statement('DROP INDEX IF EXISTS otp_codes_customer_profile_id_type_status_index');
        } catch (\Throwable $e) {
        }
        try {
            DB::statement('DROP INDEX IF EXISTS otp_codes_session_id_index');
        } catch (\Throwable $e) {
        }

        DB::statement('ALTER TABLE otp_codes RENAME TO otp_codes_old');

        Schema::create('otp_codes', function (Blueprint $table) use ($types) {
            $table->id();
            $table->foreignId('customer_profile_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable();
            $table->enum('type', $types)->default('otp');
            $table->string('code', 10)->nullable();
            $table->string('code_value')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected', 'expired'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['customer_profile_id', 'type', 'status']);
            $table->index('session_id');
        });

        DB::statement('INSERT INTO otp_codes SELECT * FROM otp_codes_old');
        DB::statement('DROP TABLE otp_codes_old');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
