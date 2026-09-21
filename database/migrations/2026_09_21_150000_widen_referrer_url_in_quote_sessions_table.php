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

        // Column was VARCHAR(255) but validation in QuoteTrackingController allows
        // up to 500 chars; real-world referrer URLs with multiple UTM/gclid/gbraid
        // params exceed 255 and were failing the insert (SQLSTATE 22001).
        DB::statement('ALTER TABLE quote_sessions MODIFY referrer_url VARCHAR(500) NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE quote_sessions MODIFY referrer_url VARCHAR(255) NULL');
    }
};
