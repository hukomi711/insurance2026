<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AdminAction Model — سجل إجراءات الأدمن
 * يسجل جميع الإجراءات الإدارية لأغراض التدقيق والمراجعة
 *
 * @property int $id
 * @property int|null $admin_id
 * @property string $action
 * @property string $target_type
 * @property int $target_id
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read \App\Models\User|null $admin
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAction whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAction whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAction whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAction whereTargetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminAction whereTargetType($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class AdminAction extends Model
{
    /**
     * Disable updated_at timestamp (audit log — write-once)
     */
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = [
        'admin_id',
        'action',
        'target_type',
        'target_id',
        'meta',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the admin who performed this action.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
