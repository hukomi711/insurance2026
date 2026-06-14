<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('action'); // export.customers, export.payments, export.payment-cards, etc
            $table->string('resource_type'); // customer_profile, payment_card, etc
            $table->integer('resource_count')->default(0); // عدد السجلات المُصدَّرة
            $table->string('format')->nullable(); // csv, html, pdf
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->enum('status', ['success', 'failure'])->default('success');
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // بيانات إضافية
            $table->timestamp('exported_at')->nullable(); // وقت العملية الفعلي
            $table->timestamps();

            // Indexes للأداء
            $table->index(['user_id', 'exported_at']);
            $table->index(['action', 'status']);
            $table->index(['resource_type']);
            $table->index(['exported_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
