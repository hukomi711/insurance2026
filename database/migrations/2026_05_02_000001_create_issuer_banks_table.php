<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Issuer banks — single source of truth for bank metadata.
 * Keys mirror config/bank_bins.php (rajhi, ahli, inma, ...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issuer_banks', function (Blueprint $table) {
            $table->id();
            $table->string('key', 32)->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->string('short_name', 64)->nullable();
            $table->char('country_code', 2)->default('SA');
            $table->string('logo_path')->nullable();
            $table->string('brand_color', 16)->nullable();
            $table->string('theme', 32)->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issuer_banks');
    }
};
