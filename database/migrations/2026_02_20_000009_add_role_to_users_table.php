<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->after('email');
        });

        // Set the admin role on the configured admin account (fallback to the legacy email).
        $adminEmail = trim((string) config('services.admin.email', 'admin@insurance.com'));
        $adminEmail = $adminEmail !== '' ? $adminEmail : 'admin@insurance.com';

        \App\Models\User::where('email', $adminEmail)->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
};
