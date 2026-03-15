<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_activities', function (Blueprint $table) {
            // Compound index for queries filtering by customer + activity type
            $table->index(
                ['customer_profile_id', 'activity_type'],
                'idx_activities_profile_type'
            );
        });
    }

    public function down(): void
    {
        Schema::table('customer_activities', function (Blueprint $table) {
            $table->dropIndex('idx_activities_profile_type');
        });
    }
};
