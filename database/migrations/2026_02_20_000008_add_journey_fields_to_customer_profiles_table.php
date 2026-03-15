<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->json('journey_history')->nullable()->after('notes');
            $table->unsignedTinyInteger('journey_completion_percentage')->default(0)->after('journey_history');
            $table->unsignedTinyInteger('current_step')->default(0)->after('journey_completion_percentage');
            $table->unsignedInteger('total_pages_visited')->default(0)->after('current_step');
            $table->string('country')->nullable()->after('location_country');
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'journey_history',
                'journey_completion_percentage',
                'current_step',
                'total_pages_visited',
                'country',
            ]);
        });
    }
};
