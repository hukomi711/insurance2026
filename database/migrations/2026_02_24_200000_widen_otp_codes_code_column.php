<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen otp_codes.code from VARCHAR(10) to VARCHAR(20).
     *
     * The 'phone_pending' placeholder (13 chars) was exceeding the old
     * 10-character limit, causing a 500 error on phone verification.
     */
    public function up(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('code', 10)->nullable()->change();
        });
    }
};
