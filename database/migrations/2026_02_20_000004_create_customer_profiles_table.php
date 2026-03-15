<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('session_id')->nullable();
            $table->string('full_name')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('national_id', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('birth_date', 10)->nullable();
            $table->string('birth_year', 4)->nullable();
            $table->string('birth_month', 2)->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->string('vehicle_model')->nullable();
            $table->string('plate_number', 20)->nullable();
            $table->string('manufacturing_year', 4)->nullable();
            $table->decimal('vehicle_price', 10, 2)->nullable();
            $table->string('insurance_type')->nullable();
            $table->string('insurance_purpose')->nullable();
            $table->string('registration_type')->nullable();
            $table->string('repair_method')->nullable();
            $table->string('sequence_number', 20)->nullable();
            $table->string('customs_card')->nullable();
            $table->boolean('has_additional_driver')->default(false);
            $table->string('additional_driver_name')->nullable();
            $table->string('additional_driver_national_id', 20)->nullable();
            $table->string('additional_driver_birth_date', 10)->nullable();
            $table->string('current_page')->nullable();
            $table->unsignedTinyInteger('completion_percentage')->default(0);
            $table->unsignedInteger('total_visits')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_activity_at')->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('device_browser', 100)->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->json('selected_insurance')->nullable();
            $table->string('nafath_username')->nullable();
            $table->boolean('nafath_verified')->default(false);
            $table->string('location_city')->nullable();
            $table->string('location_country')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('ip_address');
            $table->index('session_id');
            $table->index('user_id');
            $table->index('phone_number');
            $table->index('national_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};
