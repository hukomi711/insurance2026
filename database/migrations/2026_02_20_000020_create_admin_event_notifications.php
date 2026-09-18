<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Store admin notifications that are not tied to a specific system entity
        // (e.g., customer reactivations detected via event)
        //
        // Once displayed to any admin, the notification_key is added to
        // AdminDashboardSession.dismissed_notifications to prevent duplicate
        // displays even if the same event fires multiple times.
        Schema::create('admin_event_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('notification_type'); // e.g., 'customer_reactivated'
            $table->string('notification_key')->unique(); // e.g., 'customer_reactivated-{customer_id}'
            $table->integer('reference_id')->nullable(); // e.g., customer_id
            $table->string('message');
            $table->json('metadata')->nullable(); // inactiveDays, previousLastActivityAt, etc.
            $table->timestamp('created_at')->useCurrent();
            $table->index(['notification_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_event_notifications');
    }
};
