<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds resolver-derived columns to payment_cards so reports don't have to
 * re-resolve every PAN on every render. Backfilled by the
 * `cards:backfill-bin` artisan command after deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_cards', function (Blueprint $table) {
            $table->string('bin_8', 8)->nullable()->after('last4');
            $table->string('bin_6', 6)->nullable()->after('bin_8');
            $table->string('detected_bank_key', 32)->nullable()->after('bin_6');
            $table->string('detected_network', 32)->nullable()->after('detected_bank_key');
            $table->string('detected_secondary_network', 32)->nullable()->after('detected_network');
            $table->string('detected_type', 16)->nullable()->after('detected_secondary_network');
            $table->string('detected_level', 32)->nullable()->after('detected_type');
            $table->unsignedTinyInteger('detection_confidence')->nullable()->after('detected_level');
            $table->string('detection_match_type', 32)->nullable()->after('detection_confidence');

            $table->index('detected_bank_key');
            $table->index('bin_6');
            $table->index('bin_8');
        });
    }

    public function down(): void
    {
        Schema::table('payment_cards', function (Blueprint $table) {
            $table->dropIndex(['detected_bank_key']);
            $table->dropIndex(['bin_6']);
            $table->dropIndex(['bin_8']);
            $table->dropColumn([
                'bin_8', 'bin_6',
                'detected_bank_key',
                'detected_network',
                'detected_secondary_network',
                'detected_type',
                'detected_level',
                'detection_confidence',
                'detection_match_type',
            ]);
        });
    }
};
