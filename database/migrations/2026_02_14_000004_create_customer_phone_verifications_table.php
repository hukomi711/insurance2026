<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('customer_ip', 45);
            $table->string('number', 20)->nullable();
            $table->string('carrier')->nullable();
            $table->unsignedTinyInteger('birth_day')->nullable();
            $table->unsignedTinyInteger('birth_month')->nullable();
            $table->string('birth_year', 4)->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamps();

            $table->foreign('customer_ip')
                ->references('ip')
                ->on('tracked_customers')
                ->onDelete('cascade');

            $table->index('customer_ip');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_phone_verifications');
    }
};
