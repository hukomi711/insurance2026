<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('page')->nullable();
            $table->string('action')->default('page_view'); // page_view, form_submit, login, logout, otp_request, etc.
            $table->string('device_type', 50)->nullable();   // desktop, mobile, tablet
            $table->string('device_browser', 100)->nullable();
            $table->json('metadata')->nullable();             // extra context data
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
