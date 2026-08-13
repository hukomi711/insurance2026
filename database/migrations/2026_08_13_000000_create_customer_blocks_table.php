<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_blocks', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->nullable()->index();
            $table->string('session_id', 128)->nullable()->index();
            $table->foreignId('customer_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['ip_address', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_blocks');
    }
};
