<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Export Audit Service
 *
 * مسؤول عن تسجيل جميع عمليات التصدير والوصول للبيانات الحساسة
 */
class ExportAuditService
{
    /**
     * تسجيل عملية تصدير ناجحة
     */
    public function logSuccess(
        Request $request,
        string $action,
        string $resourceType,
        int $resourceCount,
        ?string $format = null,
        ?array $metadata = null
    ): AuditLog {
        $user = Auth::user();

        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_count' => $resourceCount,
            'format' => $format,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'success',
            'metadata' => $metadata,
            'exported_at' => now(),
        ]);
    }

    /**
     * تسجيل عملية تصدير فاشلة
     */
    public function logFailure(
        Request $request,
        string $action,
        string $resourceType,
        string $errorMessage,
        ?array $metadata = null
    ): AuditLog {
        $user = Auth::user();

        return AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'resource_type' => $resourceType,
            'resource_count' => 0,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'failure',
            'error_message' => $errorMessage,
            'metadata' => $metadata,
            'exported_at' => now(),
        ]);
    }

    /**
     * الحصول على سجل التصديرات للمستخدم الحالي
     */
    public function getUserExportHistory(int $limit = 50)
    {
        $user = Auth::user();

        return AuditLog::forUser($user->id)
            ->recentFirst()
            ->limit($limit)
            ->get();
    }

    /**
     * الحصول على إحصائيات التصديرات
     */
    public function getExportStats(int $days = 30)
    {
        $startDate = now()->subDays($days);

        return [
            'total_exports' => AuditLog::where('exported_at', '>=', $startDate)->count(),
            'successful_exports' => AuditLog::successful()->where('exported_at', '>=', $startDate)->count(),
            'failed_exports' => AuditLog::failed()->where('exported_at', '>=', $startDate)->count(),
            'exports_by_action' => AuditLog::where('exported_at', '>=', $startDate)
                ->groupBy('action')
                ->selectRaw('action, COUNT(*) as count')
                ->pluck('count', 'action'),
            'total_resources_exported' => AuditLog::successful()
                ->where('exported_at', '>=', $startDate)
                ->sum('resource_count'),
        ];
    }
}
