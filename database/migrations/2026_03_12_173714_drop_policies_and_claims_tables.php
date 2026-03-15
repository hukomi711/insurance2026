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
        // Drop claims first (has FK to policies)
        Schema::dropIfExists('claims');
        Schema::dropIfExists('policies');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tables are permanently removed — no rollback
    }
};
