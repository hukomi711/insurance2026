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
