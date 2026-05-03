<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Card BIN range table.
 *
 * Stores BIN ranges (or single BINs as bin_start=bin_end) used to resolve
 * a PAN to its issuer bank, network, type and level.
 *
 * Lookup priority (handled in CardBinResolver):
 *   1. bin_length=8 AND bin_start <= bin8 <= bin_end
 *   2. bin_length=6 AND bin_start <= bin6 <= bin_end
 *   3. any range that contains bin8 (catch-all ranges)
 *   4. fallback to legacy config/bank_bins.php prefix match
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_bin_ranges', function (Blueprint $table) {
            $table->id();
            // bin_start/bin_end are stored as left-padded numeric strings so we
            // can index/compare lexicographically without losing leading zeros.
            // Length is always 8 (we left-pad 6-digit BINs with the bin_length
            // marker). Comparison done by length-aware logic in the resolver.
            $table->unsignedBigInteger('bin_start');
            $table->unsignedBigInteger('bin_end');
            $table->unsignedTinyInteger('bin_length')->default(6); // 6 or 8

            $table->string('issuer_bank_key', 32)->nullable();
            $table->string('primary_network', 32)->nullable();   // visa | mastercard | mada | amex | discover | unionpay
            $table->string('secondary_network', 32)->nullable(); // for co-badged (mada+visa)
            $table->string('card_type', 16)->nullable();         // debit | credit | prepaid | charge
            $table->string('card_level', 32)->nullable();        // standard | classic | gold | platinum | signature | infinite | world | titanium
            $table->string('product_name')->nullable();

            $table->char('country_code', 2)->default('SA');
            $table->char('currency', 3)->default('SAR');

            $table->unsignedTinyInteger('confidence')->default(60); // 0..100
            $table->string('source', 64)->default('seed');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->index(['bin_start', 'bin_end'], 'card_bin_ranges_range_idx');
            $table->index(['issuer_bank_key']);
            $table->index(['primary_network']);
            $table->index(['is_active', 'bin_length']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_bin_ranges');
    }
};
