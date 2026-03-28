<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'email',
        'source',
        'status',
        'unsubscribed_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'unsubscribed_at' => 'datetime',
    ];

    // ─── Scopes ─────────────────────────────────────────

    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'active');
    }
}
