<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        return [
            'total'   => LoginAttempt::count(),
            'success' => LoginAttempt::successful()->count(),
            'failed'  => LoginAttempt::failed()->count(),
        ];
    }
}
