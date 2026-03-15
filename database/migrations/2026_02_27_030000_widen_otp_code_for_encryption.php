<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widen otp_codes.code to TEXT to accommodate Laravel Crypt encrypted payloads.
     *
     * Encrypted OTP values are ~220 characters (base64 JSON with iv, value, mac, tag).
     * Previous VARCHAR(20) was too small for encrypted storage.
     */
    public function up(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->text('code')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('otp_codes', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->change();
        });
    }
};
