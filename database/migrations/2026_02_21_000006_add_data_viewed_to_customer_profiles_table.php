<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تتبع حالة مشاهدة البيانات لكل عميل — مشتركة بين جميع الأدمن
 *
 * الهيكل:
 * {
 *   "vehicle":   { "at": "2026-02-21T12:00:00", "by": 1, "count": 5 },
 *   "insurance": { "at": "2026-02-21T12:05:00", "by": 2, "count": 7 },
 *   "payment":   { "at": "2026-02-21T12:10:00", "by": 1, "count": 3 }
 * }
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->json('data_viewed')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropColumn('data_viewed');
        });
    }
};
