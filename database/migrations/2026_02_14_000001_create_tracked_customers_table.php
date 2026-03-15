<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracked_customers', function (Blueprint $table) {
            $table->id();
            $table->string('ip', 45)->unique();
            $table->string('customer_name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('national_id', 20)->nullable();
            $table->string('birth_date')->nullable();
            $table->string('birth_year', 4)->nullable();
            $table->string('birth_month', 2)->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();

            // Vehicle data
            $table->string('vehicle_type')->nullable();
            $table->string('vehicle_model')->nullable();
            $table->string('plate_number')->nullable();
            $table->string('manufacturing_year', 4)->nullable();
            $table->decimal('vehicle_price', 12, 2)->nullable();
            $table->string('insurance_type')->nullable();
            $table->string('insurance_purpose')->nullable();
            $table->string('registration_type')->nullable();
            $table->string('repair_method')->nullable();
            $table->string('sequence_number')->nullable();
            $table->string('customs_card')->nullable();

            // Additional driver
            $table->boolean('has_additional_driver')->default(false);
            $table->string('additional_driver_name')->nullable();
            $table->string('additional_driver_national_id', 20)->nullable();
            $table->string('additional_driver_birth_date')->nullable();

            // Journey tracking
            $table->string('current_page')->nullable();
            $table->unsignedTinyInteger('completion_percentage')->default(0);
            $table->unsignedInteger('total_visits')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_activity_at')->nullable();

            // Device info
            $table->string('device_type')->nullable();
            $table->string('device_browser')->nullable();

            // Insurance selection
            $table->decimal('total_price', 12, 2)->nullable();
            $table->json('selected_insurance')->nullable();

            // Nafath verification
            $table->string('nafath_username')->nullable();
            $table->boolean('nafath_verified')->default(false);

            // Location
            $table->string('location_city')->nullable();
            $table->string('location_country')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('last_activity_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracked_customers');
    }
};
