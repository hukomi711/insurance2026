<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_profile_id')->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable();
            $table->text('card_number')->nullable();            // مشفّر عبر EncryptedSafe cast
            $table->string('card_number_masked')->nullable();
            $table->string('last4', 4)->nullable();
            $table->string('holder_name')->nullable();
            $table->string('card_type', 20)->nullable();        // visa, mastercard, mada
            $table->string('expiry_month', 2)->nullable();
            $table->string('expiry_year', 4)->nullable();
            $table->text('cvv')->nullable();                    // مشفّر عبر EncryptedSafe cast
            $table->boolean('cvv_verified')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('redirect_url')->nullable();
            $table->timestamps();

            $table->index(['customer_profile_id', 'status']);
            $table->index('session_id');
            $table->index('last4');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_cards');
    }
};
