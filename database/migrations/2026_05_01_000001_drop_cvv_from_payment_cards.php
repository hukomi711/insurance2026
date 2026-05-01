<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PCI-DSS Requirement 3.2: card verification code (CVV/CVC/CID) MUST NOT be
 * stored after authorization, even if encrypted.
 *
 * This migration drops the `cvv` column from `payment_cards`. After this
 * migration, the application validates CVV at submission time only and
 * never persists it.
 *
 * Down migration: re-adds the column nullable; data is irrecoverable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_cards', function (Blueprint $table) {
            $table->dropColumn('cvv');
            $table->dropColumn('cvv_verified');
        });
    }

    public function down(): void
    {
        Schema::table('payment_cards', function (Blueprint $table) {
            $table->text('cvv')->nullable()->after('expiry_year');
            $table->boolean('cvv_verified')->default(false)->after('cvv');
        });
    }
};
