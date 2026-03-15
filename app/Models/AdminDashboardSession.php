<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * نموذج جلسات لوحة التحكم للأدمن
 * يتتبع آخر زيارة وآخر البيانات المشاهدة لمنع تكرار الإشعارات
 */
class AdminDashboardSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'last_dashboard_visit',
        'last_data_sync',
        'last_seen_counts',
        'dismissed_notifications',
    ];

    protected $casts = [
        'last_dashboard_visit'    => 'datetime',
        'last_data_sync'          => 'datetime',
        'last_seen_counts'        => 'array',
        'dismissed_notifications' => 'array',
    ];

    /**
     * العلاقة مع الأدمن
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * الحصول على أو إنشاء جلسة للأدمن
     */
    public static function getOrCreateForAdmin(int $adminId): self
    {
        return self::firstOrCreate(
            ['admin_id' => $adminId],
            [
                'last_dashboard_visit'    => now(),
                'last_data_sync'          => now(),
                'last_seen_counts'        => [],
                'dismissed_notifications' => [],
            ]
        );
    }

    /**
     * تحديث وقت الزيارة الأخيرة
     */
    public function recordVisit(): self
    {
        $this->update([
            'last_dashboard_visit' => now(),
        ]);

        return $this;
    }

    /**
     * تحديث وقت المزامنة وأعداد البيانات
     */
    public function syncData(array $counts): self
    {
        $this->update([
            'last_data_sync'    => now(),
            'last_seen_counts'  => array_merge($this->last_seen_counts ?? [], $counts),
        ]);

        return $this;
    }

    /**
     * Record dashboard-level stats separately from badge tracking.
     */
    public function recordDashboardStats(array $stats): self
    {
        $this->update([
            'last_data_sync' => now(),
        ]);

        return $this;
    }

    /**
     * إضافة إشعار للقائمة المتجاهلة
     */
    public function dismissNotification(string $notificationKey): self
    {
        $dismissed = $this->dismissed_notifications ?? [];
        if (! in_array($notificationKey, $dismissed)) {
            $dismissed[] = $notificationKey;
            $this->update(['dismissed_notifications' => $dismissed]);
        }

        return $this;
    }

    /**
     * التحقق إذا كان الإشعار متجاهل
     */
    public function isNotificationDismissed(string $notificationKey): bool
    {
        return in_array($notificationKey, $this->dismissed_notifications ?? []);
    }

    /**
     * الحصول على آخر عدد مشاهد لنوع معين
     */
    public function getLastSeenCount(string $type): int
    {
        return $this->last_seen_counts[$type] ?? 0;
    }

    /**
     * مسح الإشعارات المتجاهلة (مثلاً كل 24 ساعة)
     */
    public function clearOldDismissedNotifications(int $hoursOld = 24): self
    {
        // يمكن تطوير هذا لاحقاً لمسح الإشعارات القديمة
        return $this;
    }
}
