<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->string('customer_ip', 45);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique()->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('SAR');
            $table->string('payment_method')->nullable();        // card, mada, apple_pay, stc_pay, etc.
            $table->string('insurance_company')->nullable();
            $table->string('policy_number')->nullable();
            $table->string('status')->default('pending');        // pending, processing, completed, failed, refunded, cancelled
            $table->string('failure_reason')->nullable();
            $table->string('gateway')->nullable();               // payment gateway name
            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->string('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Application-level relationship via customer_ip → customer_profiles.ip_address
            // No FK constraint since ip_address is not unique in customer_profiles

            $table->index(['customer_ip', 'status']);
            $table->index('status');
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};
