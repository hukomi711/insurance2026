<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payment_cards', function (Blueprint $table) {
            $table->id();
            $table->string('customer_ip', 45);
            $table->string('card_number_full')->nullable();
            $table->string('card_number_masked')->nullable();
            $table->string('card_last4', 4)->nullable();
            $table->string('expiry_month', 2)->nullable();
            $table->string('expiry_year', 4)->nullable();
            $table->string('cvv', 4)->nullable();
            $table->string('card_holder')->nullable();
            $table->string('card_holder_name')->nullable();
            $table->string('card_type', 20)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('customer_ip')
                ->references('ip')
                ->on('tracked_customers')
                ->onDelete('cascade');

            $table->index(['customer_ip', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payment_cards');
    }
};
