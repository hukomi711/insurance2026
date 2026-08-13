<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Check if columns exist before adding them
            if (!Schema::hasColumn('orders', 'company_id')) {
                $table->unsignedInteger('company_id')->nullable()->after('plan_id');
            }
            if (!Schema::hasColumn('orders', 'plan_sub_type')) {
                $table->string('plan_sub_type')->nullable()->after('plan_type');
            }
            if (!Schema::hasColumn('orders', 'pricing_signature')) {
                $table->string('pricing_signature')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('orders', 'pricing_timestamp')) {
                $table->unsignedBigInteger('pricing_timestamp')->nullable()->after('pricing_signature');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'company_id',
                'plan_sub_type',
                'pricing_signature',
                'pricing_timestamp',
            ]);
        });
    }
};
