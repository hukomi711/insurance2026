<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add foreign key constraint on admin_actions.admin_id → users.id.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Make admin_id nullable so SET NULL on delete works
        Schema::table('admin_actions', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_id')->nullable()->change();
        });

        // Add FK only if it doesn't already exist (cross-DB compatible)
        $driver = Schema::getConnection()->getDriverName();

        $hasFk = false;
        if ($driver === 'mysql') {
            $existingFk = DB::select(
                "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_NAME = 'admin_actions'
                   AND TABLE_SCHEMA = DATABASE()
                   AND COLUMN_NAME = 'admin_id'
                   AND REFERENCED_TABLE_NAME IS NOT NULL
                 LIMIT 1"
            );
            $hasFk = ! empty($existingFk);
        }

        if (! $hasFk) {
            Schema::table('admin_actions', function (Blueprint $table) {
                $table->foreign('admin_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('admin_actions', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
        });

        Schema::table('admin_actions', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_id')->nullable(false)->change();
        });
    }
};
