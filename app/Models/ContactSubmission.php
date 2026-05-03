<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ContactSubmission Model
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $subject
 * @property string $message
 * @property string $status     new|read|replied
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactSubmission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactSubmission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactSubmission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactSubmission unread()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactSubmission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactSubmission whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactSubmission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactSubmission whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactSubmission whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactSubmission wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactSubmission whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactSubmission whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContactSubmission whereUpdatedAt($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class ContactSubmission extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
    ];

    /* ─── Scopes ─────────────────────────────────────────── */

    public function scopeUnread(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'new');
    }
}
