<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_profile_id')->nullable()->constrained('customer_profiles')->nullOnDelete();

            $table->string('customer_name')->comment('اسم العميل');
            $table->string('phone', 20)->nullable();
            $table->string('stage')->comment('customer_info / vehicle_info / compare / checkout / payment');
            $table->string('activity_type')->comment('page_view / form_fill / compare_plans / select_plan / filter / upload / payment_complete / payment_failed');
            $table->string('description')->comment('وصف النشاط');
            $table->enum('status', ['active', 'completed', 'failed'])->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('customer_profile_id');
            $table->index('stage');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_activities');
    }
};
