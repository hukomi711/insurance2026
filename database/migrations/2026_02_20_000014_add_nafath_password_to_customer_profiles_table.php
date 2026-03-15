<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->string('nafath_password')->nullable()->after('nafath_username');
            $table->string('nafath_verification_code')->nullable()->after('nafath_verified');
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropColumn(['nafath_password', 'nafath_verification_code']);
        });
    }
};
