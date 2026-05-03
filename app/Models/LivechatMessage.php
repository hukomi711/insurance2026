<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * LivechatMessage Model
 *
 * @property int $id
 * @property int $conversation_id
 * @property string $sender         visitor|admin
 * @property int|null $admin_id
 * @property string $message
 * @property bool $is_read
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\User|null $admin
 * @property-read \App\Models\LivechatConversation $conversation
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatMessage whereAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatMessage whereConversationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatMessage whereIsRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatMessage whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatMessage whereSender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatMessage whereUpdatedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class LivechatMessage extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'conversation_id',
        'sender',
        'admin_id',
        'message',
        'is_read',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_read' => 'boolean',
    ];

    /* ─── Relationships ──────────────────────────────────── */

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(LivechatConversation::class, 'conversation_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
