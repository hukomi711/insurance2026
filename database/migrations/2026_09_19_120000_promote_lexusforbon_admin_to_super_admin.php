<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        User::where('email', 'admin@lexusforbon.com')->update(['role' => 'super_admin']);
    }

    public function down(): void
    {
        User::where('email', 'admin@lexusforbon.com')->where('role', 'super_admin')->update(['role' => 'admin']);
    }
};
