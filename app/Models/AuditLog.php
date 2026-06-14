<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit Log - تسجيل جميع عمليات التصدير والتعديلات الحساسة
 *
 * Fields:
 * - user_id: المستخدم الذي قام بالعملية
 * - action: نوع العملية (export.customers, export.payments, export.payment-cards, etc)
 * - resource_type: نوع المورد (customer_profile, payment_card, etc)
 * - resource_count: عدد السجلات المُصدَّرة
 * - format: صيغة التصدير (csv, html, pdf)
 * - ip_address: IP المستخدم
 * - user_agent: User-Agent من الطلب
 * - status: success | failure
 * - error_message: إذا فشلت العملية
 * - metadata: بيانات إضافية (JSON)
 * - exported_at: وقت التصدير
 */
class AuditLog extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'resource_type',
        'resource_count',
        'format',
        'ip_address',
        'user_agent',
        'status',
        'error_message',
        'metadata',
        'exported_at',
    ];

    protected $casts = [
        'metadata' => 'json',
        'exported_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * العلاقة مع المستخدم
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failure');
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderByDesc('exported_at');
    }
}
