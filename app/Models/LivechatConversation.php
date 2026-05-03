<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * LivechatConversation Model
 *
 * @property int $id
 * @property string $session_id
 * @property string|null $visitor_ip
 * @property string $visitor_name
 * @property string|null $visitor_page
 * @property string $status        active|closed|archived
 * @property int $unread_count
 * @property string|null $last_message
 * @property \Illuminate\Support\Carbon|null $last_message_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LivechatMessage> $messages
 * @property-read int|null $messages_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatConversation active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatConversation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatConversation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatConversation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatConversation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatConversation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatConversation whereLastMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatConversation whereLastMessageAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatConversation whereSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatConversation whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatConversation whereUnreadCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatConversation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatConversation whereVisitorIp($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatConversation whereVisitorName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatConversation whereVisitorPage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LivechatConversation withUnread()
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class LivechatConversation extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'session_id',
        'visitor_ip',
        'visitor_name',
        'visitor_page',
        'status',
        'unread_count',
        'last_message',
        'last_message_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'unread_count'    => 'integer',
        'last_message_at' => 'datetime',
    ];

    /* ─── Relationships ──────────────────────────────────── */

    public function messages(): HasMany
    {
        return $this->hasMany(LivechatMessage::class, 'conversation_id');
    }

    /* ─── Scopes ─────────────────────────────────────────── */

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'active');
    }

    public function scopeWithUnread(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('unread_count', '>', 0);
    }
}
