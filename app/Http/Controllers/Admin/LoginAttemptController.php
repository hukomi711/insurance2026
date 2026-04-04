<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LoginAttemptController extends Controller
{
    /**
     * قائمة محاولات تسجيل الدخول مع البحث والتصفية
     */
    public function index(Request $request): JsonResponse
    {
        $query = LoginAttempt::with('user:id,name,email')
            ->search($request->input('search'))
            ->when(
                $request->filled('status') && $request->input('status') !== 'all',
                fn($q) => $q->where('status', $request->input('status'))
            )
            ->orderByDesc('created_at');

        $attempts = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'success'  => true,
            'attempts' => $attempts->items(),
            'meta'     => [
                'current_page' => $attempts->currentPage(),
                'last_page'    => $attempts->lastPage(),
                'per_page'     => $attempts->perPage(),
                'total'        => $attempts->total(),
            ],
            'stats' => $this->getStats(),
        ]);
    }

    /**
     * إحصائيات المحاولات
     */
    private function getStats(): array
    {
        return Cache::remember('admin:login_attempt_stats', 60, function () {
            $stats = LoginAttempt::where('created_at', '>=', now()->subDays(30))
                ->selectRaw("COUNT(*) as total")
                ->selectRaw("SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success")
                ->selectRaw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed")
                ->first();

            return [
                'total'   => (int) $stats->total,
                'success' => (int) $stats->success,
                'failed'  => (int) $stats->failed,
            ];
        });
    }
}
