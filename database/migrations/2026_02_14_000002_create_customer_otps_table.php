<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_otps', function (Blueprint $table) {
            $table->id();
            $table->string('customer_ip', 45);
            $table->string('code', 10);
            $table->unsignedTinyInteger('length')->default(6);
            $table->enum('type', ['otp', 'pin'])->default('otp');
            $table->enum('status', ['pending', 'verified', 'rejected', 'expired'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('customer_ip')
                ->references('ip')
                ->on('tracked_customers')
                ->onDelete('cascade');

            $table->index(['customer_ip', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_otps');
    }
};
