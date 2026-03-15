<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix customer profile uniqueness:
 *
 * 1. Drop the (ip_address, session_id) composite unique — this was the root cause
 *    of customer card duplication (session_id changes on tab close, new tab, sendBeacon).
 * 2. Add a unique index on national_id (where not null) — each person gets ONE card.
 * 3. Widen current_page from varchar(255) to varchar(1024) for long Arabic URLs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            // Drop the problematic composite unique constraint
            $table->dropUnique('cp_ip_session_unique');
        });

        // Add unique index on national_id (nullable unique — only enforced when not null)
        // This must be done in a separate Schema::table call after the drop
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->unique('national_id', 'cp_national_id_unique');
        });

        // Widen current_page for long Arabic blog image URLs
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->string('current_page', 1024)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropUnique('cp_national_id_unique');
        });

        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->unique(['ip_address', 'session_id'], 'cp_ip_session_unique');
        });

        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->string('current_page', 255)->nullable()->change();
        });
    }
};
