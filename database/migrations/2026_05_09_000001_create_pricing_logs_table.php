<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');

            // Quote details
            $table->string('plan_id', 50)->index();  // e.g., "company_1_thirdParty_1000"
            $table->unsignedInteger('quoted_price');  // Total price with VAT (SAR)
            $table->unsignedInteger('base_price');    // Base price before factors

            // Pricing breakdown
            $table->json('factors')->nullable();  // {vehicle: 0.95, driver: 1.35, ...}
            $table->string('pricing_version', 20)->default('1.0.0');

            // Request context
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('context', 50)->index();  // 'quote_calculation', 'order_submission', 'recalculation'
            $table->json('metadata')->nullable();   // Additional context

            $table->timestamp('created_at')->useCurrent();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pricing_logs');
    }
};
