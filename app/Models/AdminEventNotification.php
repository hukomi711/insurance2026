<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $notification_type  e.g., 'customer_reactivated'
 * @property string $notification_key   unique key for deduplication
 * @property int|null $reference_id     e.g., customer_id
 * @property string $message            display message
 * @property array<string, mixed>|null $metadata  inactiveDays, previousLastActivityAt, ipAddress, etc.
 * @property \Illuminate\Support\Carbon $created_at
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class AdminEventNotification extends Model
{
    public const UPDATED_AT = null; // immutable after creation

    protected $fillable = [
        'notification_type',
        'notification_key',
        'reference_id',
        'message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Find or create a notification by its unique key.
     * Used to prevent duplicate notifications for the same event.
     */
    public static function findOrCreateByKey(
        string $type,
        string $key,
        int $referenceId,
        string $message,
        array $metadata = []
    ): self {
        return self::firstOrCreate(
            ['notification_key' => $key],
            [
                'notification_type' => $type,
                'reference_id' => $referenceId,
                'message' => $message,
                'metadata' => $metadata,
            ]
        );
    }

    /**
     * Get recent notifications not yet dismissed.
     *
     * @param array<string> $dismissedKeys  list of notification keys already marked as read
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecentNotDismissed(array $dismissedKeys = [], int $limit = 5)
    {
        $query = self::whereNotIn('notification_key', $dismissedKeys ?: ['__none__'])
            ->latest('created_at')
            ->take($limit);

        return $query->get();
    }
}
