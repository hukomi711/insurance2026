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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('policy_number')->unique()->nullable();
            $table->foreignId('customer_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index();

            // Plan details
            $table->unsignedInteger('plan_id')->nullable();
            $table->string('plan_name')->nullable();
            $table->string('insurance_company')->nullable();
            $table->string('insurance_type')->nullable();
            $table->string('plan_type')->nullable();

            // Pricing
            $table->decimal('subtotal', 10, 2);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->unsignedInteger('deductible')->default(0);
            $table->json('addons')->nullable();
            $table->json('pricing_factors')->nullable();

            // Applicant
            $table->string('applicant_name')->nullable();
            $table->string('applicant_national_id')->nullable();
            $table->string('applicant_phone')->nullable();
            $table->string('applicant_email')->nullable();

            // Vehicle
            $table->string('vehicle_plate')->nullable();
            $table->string('vehicle_make')->nullable();
            $table->string('vehicle_model')->nullable();
            $table->unsignedSmallInteger('vehicle_year')->nullable();

            // Policy dates
            $table->date('policy_start_date')->nullable();
            $table->date('policy_end_date')->nullable();

            // Payment
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('pending');

            // Status
            $table->string('status')->default('pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
