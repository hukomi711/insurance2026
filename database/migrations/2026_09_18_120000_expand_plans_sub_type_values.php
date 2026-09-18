<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement(
            "ALTER TABLE plans MODIFY sub_type ENUM('thirdParty', 'thirdPartyPlus', 'vehicleDamagePlus', 'comprehensive') NOT NULL"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement(
            "ALTER TABLE plans MODIFY sub_type ENUM('thirdParty', 'comprehensive') NOT NULL"
        );
    }
};
