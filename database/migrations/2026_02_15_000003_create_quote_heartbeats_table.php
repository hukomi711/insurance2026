<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_session_id')->constrained('quote_sessions')->cascadeOnDelete();
            $table->string('current_step', 50);
            $table->string('customer_ip', 45)->nullable();

            // Page visibility
            $table->boolean('tab_visible')->default(true);

            $table->timestamp('pinged_at')->useCurrent();

            // Index for cleanup queries
            $table->index('pinged_at');
            $table->index('quote_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_heartbeats');
    }
};
