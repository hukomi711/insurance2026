<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_step_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_session_id')->constrained('quote_sessions')->cascadeOnDelete();
            $table->string('step_name', 50);
            $table->unsignedTinyInteger('step_number');

            // Timing
            $table->timestamp('entered_at')->useCurrent();
            $table->timestamp('exited_at')->nullable();
            $table->unsignedInteger('duration_seconds')->default(0);

            // Interaction details
            $table->json('form_snapshot')->nullable();
            $table->string('exit_reason', 30)->nullable(); // next, back, abandon, refresh
            $table->unsignedSmallInteger('interaction_count')->default(0);

            $table->timestamps();

            // Indexes
            $table->index('step_name');
            $table->index(['quote_session_id', 'step_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_step_logs');
    }
};
