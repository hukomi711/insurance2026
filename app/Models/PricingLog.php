<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PricingLog
 *
 * Audit trail for all pricing calculations.
 * Enables dispute resolution and fraud detection.
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $order_id
 * @property string $plan_id
 * @property int $quoted_price - Total price (with VAT)
 * @property int $base_price
 * @property array $factors - Pricing breakdown (vehicle, driver, lifestyle, policy, etc.)
 * @property string $pricing_version - Version of pricing engine used
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string $context - 'quote_calculation', 'order_submission', 'recalculation'
 * @property array|null $metadata - Additional context
 * @property \Carbon\Carbon $created_at
 */
class PricingLog extends Model
{
    use HasFactory;

    protected $table = 'pricing_logs';

    protected $fillable = [
        'user_id',
        'order_id',
        'plan_id',
        'quoted_price',
        'base_price',
        'factors',
        'pricing_version',
        'ip_address',
        'user_agent',
        'context',
        'metadata',
    ];

    protected $casts = [
        'factors' => 'array',
        'metadata' => 'array',
        'quoted_price' => 'integer',
        'base_price' => 'integer',
    ];

    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function ($model) {
            $model->created_at = now();
        });
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Scopes
    public function scopeForContext(Builder $query, string $context): Builder
    {
        return $query->where('context', $context);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
