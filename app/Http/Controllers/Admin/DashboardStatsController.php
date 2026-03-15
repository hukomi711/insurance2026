<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class DashboardStatsController extends Controller
{
    /**
     * Dashboard summary stats.
     *
     * GET /api/admin/dashboard/stats
     *
     * Uses Cache::flexible() for stampede protection:
     *   - [30, 90] = serve fresh for 30s, then serve stale while one request refreshes,
     *     hard-expire at 90s. Prevents thundering herd after observer invalidation.
     */
    public function stats(): JsonResponse
    {
        $data = Cache::flexible('admin:dashboard:stats', [30, 90], function () {
            // Single query for customers: total + new this month
            $customerAgg = CustomerProfile::selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as new_this_month
            ", [now()->startOfMonth()])->first();

            return [
                'totalCustomers'        => (int) $customerAgg->total,
                'newCustomersThisMonth'  => (int) $customerAgg->new_this_month,
            ];
        });

        return response()->json($data);
    }

    /**
     * Monthly sales breakdown.
     *
     * GET /api/admin/dashboard/sales/monthly
     *
     * Cache::flexible [120, 600] = fresh for 2min, stale-while-revalidate up to 10min.
     */
    public function monthlySales(): JsonResponse
    {
        $data = Cache::flexible('admin:dashboard:monthly_sales', [120, 600], function () {
            $months = [
                1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
                5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
                9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
            ];

            $data = [];
            foreach ($months as $num => $name) {
                $data[] = [
                    'month'         => $name,
                    'comprehensive' => 0,
                    'thirdParty'    => 0,
                    'revenue'       => 0,
                ];
            }

            return $data;
        });

        return response()->json($data);
    }

    /**
     * Soft-invalidate dashboard caches.
     */
    public static function flushCache(): void
    {
        Cache::forget('admin:dashboard:stats');
        Cache::forget('admin:dashboard:monthly_sales');
    }
}
