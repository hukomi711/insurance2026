<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasIndex(string $table, string $index): bool
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return false;
        }

        $db = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$db, $table, $index]
        );

        return ((int) ($row->c ?? 0)) > 0;
    }

    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        if (Schema::hasTable('customer_profiles')) {
            if (! $this->hasIndex('customer_profiles', 'cp_ip_last_id_idx')) {
                DB::statement('ALTER TABLE customer_profiles ADD INDEX cp_ip_last_id_idx (ip_address, last_activity_at, id)');
            }
            if (! $this->hasIndex('customer_profiles', 'cp_last_activity_id_idx')) {
                DB::statement('ALTER TABLE customer_profiles ADD INDEX cp_last_activity_id_idx (last_activity_at, id)');
            }
        }

        if (Schema::hasTable('otp_codes') && ! $this->hasIndex('otp_codes', 'otp_customer_created_type_idx')) {
            DB::statement('ALTER TABLE otp_codes ADD INDEX otp_customer_created_type_idx (customer_profile_id, created_at, type)');
        }

        if (Schema::hasTable('payment_cards') && ! $this->hasIndex('payment_cards', 'pc_customer_created_idx')) {
            DB::statement('ALTER TABLE payment_cards ADD INDEX pc_customer_created_idx (customer_profile_id, created_at)');
        }
    }

    public function down(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        if (Schema::hasTable('payment_cards') && $this->hasIndex('payment_cards', 'pc_customer_created_idx')) {
            DB::statement('ALTER TABLE payment_cards DROP INDEX pc_customer_created_idx');
        }

        if (Schema::hasTable('otp_codes') && $this->hasIndex('otp_codes', 'otp_customer_created_type_idx')) {
            DB::statement('ALTER TABLE otp_codes DROP INDEX otp_customer_created_type_idx');
        }

        if (Schema::hasTable('customer_profiles')) {
            if ($this->hasIndex('customer_profiles', 'cp_last_activity_id_idx')) {
                DB::statement('ALTER TABLE customer_profiles DROP INDEX cp_last_activity_id_idx');
            }
            if ($this->hasIndex('customer_profiles', 'cp_ip_last_id_idx')) {
                DB::statement('ALTER TABLE customer_profiles DROP INDEX cp_ip_last_id_idx');
            }
        }
    }
};
