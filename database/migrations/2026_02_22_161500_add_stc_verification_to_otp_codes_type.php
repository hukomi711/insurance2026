<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support ALTER COLUMN for enums.
        // Recreate the table with the new enum value.
        Schema::table('otp_codes', function (Blueprint $table) {
            // For SQLite we must recreate; for MySQL we can alter.
            // Using raw SQL approach that works for both.
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: rename → recreate → copy → drop old
            DB::statement('PRAGMA foreign_keys = OFF');

            // Drop indexes before rename to avoid conflicts
            try { DB::statement('DROP INDEX IF EXISTS otp_codes_customer_profile_id_type_status_index'); } catch (\Throwable $e) {}
            try { DB::statement('DROP INDEX IF EXISTS otp_codes_session_id_index'); } catch (\Throwable $e) {}

            DB::statement('ALTER TABLE otp_codes RENAME TO otp_codes_old');

            Schema::create('otp_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_profile_id')->constrained()->cascadeOnDelete();
                $table->string('session_id')->nullable();
                $table->enum('type', ['otp', 'pin', 'phone', 'email', 'stc_verification'])->default('otp');
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
        } else {
            // MySQL/MariaDB: simple column modify
            DB::statement("ALTER TABLE otp_codes MODIFY COLUMN `type` ENUM('otp','pin','phone','email','stc_verification') NOT NULL DEFAULT 'otp'");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');

            // Remove any stc_verification rows first
            DB::table('otp_codes')->where('type', 'stc_verification')->update(['type' => 'phone']);

            // Drop indexes before rename to avoid conflicts
            try { DB::statement('DROP INDEX IF EXISTS otp_codes_customer_profile_id_type_status_index'); } catch (\Throwable $e) {}
            try { DB::statement('DROP INDEX IF EXISTS otp_codes_session_id_index'); } catch (\Throwable $e) {}

            DB::statement('ALTER TABLE otp_codes RENAME TO otp_codes_old');

            Schema::create('otp_codes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_profile_id')->constrained()->cascadeOnDelete();
                $table->string('session_id')->nullable();
                $table->enum('type', ['otp', 'pin', 'phone', 'email'])->default('otp');
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
        } else {
            DB::statement("ALTER TABLE otp_codes MODIFY COLUMN `type` ENUM('otp','pin','phone','email') NOT NULL DEFAULT 'otp'");
        }
    }
};
