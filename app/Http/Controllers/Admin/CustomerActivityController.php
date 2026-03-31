<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CustomerActivityController extends Controller
{
    /**
     * قائمة أنشطة العملاء مع البحث والتصفية
     */
    public function index(Request $request): JsonResponse
    {
        $allowedStages = ['customer_info', 'vehicle_info', 'compare', 'checkout', 'payment', 'mojaz'];
        $allowedStatuses = ['active', 'completed', 'failed'];
        $perPage = min((int) $request->input('per_page', 30), 100);

        $query = CustomerActivity::with('customerProfile:id,full_name,phone_number,ip_address')
            ->search($request->input('search'))
            ->when(
                $request->filled('stage') && in_array($request->input('stage'), $allowedStages, true),
                fn($q) => $q->ofStage($request->input('stage'))
            )
            ->when(
                $request->filled('status') && in_array($request->input('status'), $allowedStatuses, true),
                fn($q) => $q->where('status', $request->input('status'))
            )
            ->orderByDesc('created_at');

        $activities = $query->paginate($perPage);

        return response()->json([
            'success'    => true,
            'activities' => $activities->items(),
            'meta'       => [
                'current_page' => $activities->currentPage(),
                'last_page'    => $activities->lastPage(),
                'per_page'     => $activities->perPage(),
                'total'        => $activities->total(),
            ],
            'stats' => $this->getStats(),
        ]);
    }

    /**
     * إحصائيات الأنشطة
     *
     * Optimized: 4 COUNT queries → 1 with conditional aggregation.
     * Cached for 10 seconds to handle concurrent admin requests.
     */
    private function getStats(): array
    {
        return Cache::remember('admin:activity_stats', 30, function () {
            $row = CustomerActivity::query()
                ->where('created_at', '>=', now()->subDays(7))
                ->select([
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count"),
                    DB::raw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count"),
                    DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count"),
                ])
                ->first();

            return [
                'total'     => (int) $row->total,
                'active'    => (int) $row->active_count,
                'completed' => (int) $row->completed_count,
                'failed'    => (int) $row->failed_count,
            ];
        });
    }
}
