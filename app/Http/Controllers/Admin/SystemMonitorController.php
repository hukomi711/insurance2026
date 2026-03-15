<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * SystemMonitorController — مراقبة صحة النظام
 *
 * يوفر بيانات فورية عن:
 * - حالة الصف (Queue)
 * - صحة النظام (Redis, Database)
 * - مساحة القرص
 * - سجلات الأخطاء الأخيرة
 */
class SystemMonitorController extends Controller
{
    /**
     * الحصول على جميع إحصائيات النظام
     * GET /api/admin/system/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $stats = Cache::remember('admin.system.stats', 5, function () {
            return [
                'queue'     => $this->getQueueStats(),
                'system'    => $this->getSystemHealth(),
                'errors'    => $this->getRecentErrors(),
                'timestamp' => now()->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    /**
     * صحة النظام فقط (lightweight)
     * GET /api/admin/system/health
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $this->getSystemHealth(),
        ]);
    }

    /**
     * مسح الكاش لإعادة تحميل البيانات
     * POST /api/admin/system/clear-cache
     */
    public function clearCache(): JsonResponse
    {
        Cache::forget('admin.system.stats');

        return response()->json([
            'success' => true,
            'message' => 'تم مسح الكاش بنجاح',
            'data'    => Cache::remember('admin.system.stats', 5, fn () => [
                'queue'     => $this->getQueueStats(),
                'system'    => $this->getSystemHealth(),
                'errors'    => $this->getRecentErrors(),
                'timestamp' => now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * 📮 إحصائيات الصف (Queue)
     */
    private function getQueueStats(): array
    {
        try {
            $pending = 0;
            $failed = 0;

            if (config('queue.default') === 'redis') {
                $pending = Redis::llen('queues:default') ?? 0;
            } else {
                $pending = DB::table('jobs')->count();
            }

            $failed = DB::table('failed_jobs')->count();

            return [
                'pending' => $pending,
                'failed'  => $failed,
                'status'  => $pending === 0 ? 'idle' : 'processing',
            ];
        } catch (\Exception $e) {
            return [
                'pending' => 0,
                'failed'  => 0,
                'status'  => 'error',
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * 💚 صحة النظام (Database + Redis + Storage)
     */
    private function getSystemHealth(): array
    {
        $health = [
            'database'  => 'healthy',
            'redis'     => 'healthy',
            'storage'   => 'healthy',
            'diskSpace' => $this->getDiskSpace(),
        ];

        // فحص Database
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $health['database'] = 'unhealthy';
        }

        // فحص Redis
        try {
            Redis::ping();
        } catch (\Exception $e) {
            $health['redis'] = 'unhealthy';
        }

        // فحص المساحة التخزينية
        try {
            $storagePath = storage_path();
            if (is_dir($storagePath)) {
                $free = disk_free_space($storagePath);
                $total = disk_total_space($storagePath);
                if ($free && $total) {
                    $percentUsed = (($total - $free) / $total) * 100;
                    if ($percentUsed > 90) {
                        $health['storage'] = 'warning';
                    }
                }
            }
        } catch (\Exception $e) {
            // ignore
        }

        return $health;
    }

    /**
     * 💾 مساحة القرص
     */
    private function getDiskSpace(): array
    {
        try {
            $total = disk_total_space('/');
            $free = disk_free_space('/');
            $used = $total - $free;

            return [
                'total'       => $this->formatBytes($total),
                'used'        => $this->formatBytes($used),
                'free'        => $this->formatBytes($free),
                'percentUsed' => round(($used / $total) * 100, 2),
            ];
        } catch (\Exception $e) {
            return [
                'total'       => 'N/A',
                'used'        => 'N/A',
                'free'        => 'N/A',
                'percentUsed' => 0,
            ];
        }
    }

    /**
     * 🔴 الأخطاء الأخيرة من ملف اللوغ
     */
    private function getRecentErrors(): array
    {
        try {
            $logPath = storage_path('logs/laravel.log');

            if (! file_exists($logPath)) {
                return [];
            }

            // Read last 50 lines efficiently using SplFileObject (avoids loading entire file)
            $file = new \SplFileObject($logPath, 'r');
            $file->seek(PHP_INT_MAX); // jump to end
            $totalLines = $file->key();
            $startLine = max(0, $totalLines - 50);
            $lines = [];
            $file->seek($startLine);
            while (! $file->eof()) {
                $line = trim($file->fgets());
                if ($line !== '') {
                    $lines[] = $line;
                }
            }

            $errors = [];
            foreach (array_reverse($lines) as $line) {
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*?(ERROR|CRITICAL):\s*(.+)/', $line, $matches)) {
                    $errors[] = [
                        'timestamp' => $matches[1],
                        'level'     => $matches[2],
                        'message'   => mb_substr($matches[3], 0, 150),
                    ];
                }
            }

            return array_slice($errors, 0, 10); // آخر 10 أخطاء
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * تحويل البايتات إلى صيغة قابلة للقراءة
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
