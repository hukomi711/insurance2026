<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class QuoteSession extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'uuid',
        'customer_ip',
        'current_step',
        'step_number',
        'insurance_type',
        'vehicle_data',
        'personal_data',
        'comparison_data',
        'status',
        'completion_percentage',
        'device_type',
        'device_browser',
        'user_agent',
        'started_at',
        'last_heartbeat_at',
        'completed_at',
        'abandoned_at',
        'total_duration_seconds',
        'referrer_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'vehicle_data' => 'array',
        'personal_data' => 'array',
        'comparison_data' => 'array',
        'completion_percentage' => 'integer',
        'total_duration_seconds' => 'integer',
        'started_at' => 'datetime',
        'last_heartbeat_at' => 'datetime',
        'completed_at' => 'datetime',
        'abandoned_at' => 'datetime',
    ];

    /**
     * Boot — auto-generate UUID on creation
     */
    protected static function booted(): void
    {
        static::creating(function (QuoteSession $session) {
            if (empty($session->uuid)) {
                $session->uuid = (string) Str::uuid();
            }
        });
    }

    // ─── Relationships ──────────────────────────────────────

    public function stepLogs(): HasMany
    {
        return $this->hasMany(QuoteStepLog::class);
    }

    public function heartbeats(): HasMany
    {
        return $this->hasMany(QuoteHeartbeat::class);
    }

    // ─── Scopes ─────────────────────────────────────────────

    public function scopeActive(Builder $query)
    {
        return $query->where('status', 'active');
    }

    public function scopeAbandoned(Builder $query)
    {
        return $query->where('status', 'abandoned');
    }

    public function scopeCompleted(Builder $query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Sessions with no heartbeat within the given minutes (stale)
     */
    public function scopeStale(Builder $query, int $minutes = 5)
    {
        return $query->where('status', 'active')
            ->where('last_heartbeat_at', '<', now()->subMinutes($minutes));
    }

    /**
     * Sessions that are currently live (heartbeat within threshold)
     */
    public function scopeLive(Builder $query, int $minutes = 2)
    {
        return $query->where('status', 'active')
            ->where('last_heartbeat_at', '>=', now()->subMinutes($minutes));
    }

    // ─── Helpers ────────────────────────────────────────────

    /**
     * Mark this session as completed
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completion_percentage' => 100,
            'completed_at' => now(),
            'total_duration_seconds' => $this->started_at
                ? (int) abs(now()->diffInSeconds($this->started_at))
                : 0,
        ]);
    }

    /**
     * Mark this session as abandoned
     */
    public function markAbandoned(): void
    {
        $this->update([
            'status' => 'abandoned',
            'abandoned_at' => now(),
            'total_duration_seconds' => $this->started_at
                ? (int) abs(($this->last_heartbeat_at ?? now())->diffInSeconds($this->started_at))
                : 0,
        ]);
    }

    /**
     * Step name → completion percentage mapping
     */
    public static function stepCompletionMap(): array
    {
        return [
            'motorapp' => 10,
            'vehicle' => 40,
            'compare' => 70,
            'details' => 90,
            'completed' => 100,
        ];
    }

    /**
     * Get formatted duration string
     */
    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->total_duration_seconds;
        if ($seconds < 60) return $seconds . ' ثانية';
        if ($seconds < 3600) return round($seconds / 60) . ' دقيقة';
        return round($seconds / 3600, 1) . ' ساعة';
    }

    /**
     * API response format
     */
    public function toMonitorFormat(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'customer_ip' => $this->customer_ip,
            'current_step' => $this->current_step,
            'step_number' => $this->step_number,
            'insurance_type' => $this->insurance_type,
            'status' => $this->status,
            'completion_percentage' => $this->completion_percentage,
            'device_type' => $this->device_type,
            'device_browser' => $this->device_browser,
            'started_at' => $this->started_at?->toISOString(),
            'last_heartbeat_at' => $this->last_heartbeat_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'abandoned_at' => $this->abandoned_at?->toISOString(),
            'total_duration_seconds' => $this->total_duration_seconds,
            'formatted_duration' => $this->formatted_duration,
            'vehicle_data' => $this->vehicle_data,
            'personal_data' => $this->personal_data,
            'step_logs' => $this->stepLogs->sortBy('step_number')->values(),
        ];
    }
}
