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
