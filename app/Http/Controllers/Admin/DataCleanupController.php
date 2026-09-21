<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DataCleanupController extends Controller
{
    /**
     * Customer & transaction data only. Admin/reference tables
     * (users, plans, audit_logs, admin_actions, site_settings,
     * issuer_banks, card_bin_ranges, customer_blocks, ...) are never touched.
     */
    private const CLEARABLE_TABLES = [
        'tracked_customers',
        'customer_otps',
        'customer_payment_cards',
        'customer_phone_verifications',
        'quote_sessions',
        'quote_step_logs',
        'quote_heartbeats',
        'phone_verifications',
        'payment_requests',
        'customer_profiles',
        'payment_cards',
        'otp_codes',
        'customer_activities',
        'login_attempts',
        'livechat_conversations',
        'livechat_messages',
        'contact_submissions',
        'orders',
        'newsletter_subscribers',
        'funnel_events',
        'email_logs',
        'pricing_logs',
    ];

    /**
     * Irreversibly clear all customer & transaction data.
     * POST /api/admin/system/clear-customer-data
     */
    public function clearCustomerData(Request $request): JsonResponse
    {
        $counts = [];

        Schema::disableForeignKeyConstraints();
        foreach (self::CLEARABLE_TABLES as $table) {
            if (Schema::hasTable($table)) {
                $counts[$table] = DB::table($table)->count();
                DB::table($table)->truncate();
            }
        }
        Schema::enableForeignKeyConstraints();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'data.bulk_delete_customer_data',
            'resource_type' => 'customer_and_transaction_data',
            'resource_count' => array_sum($counts),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
            'metadata' => $counts,
            'exported_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف جميع بيانات العملاء والمعاملات بنجاح.',
            'data' => ['cleared' => $counts],
        ]);
    }
}
