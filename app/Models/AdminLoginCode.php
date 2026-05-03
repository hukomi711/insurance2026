<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $code
 * @property string $ip_address
 * @property bool $used
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminLoginCode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminLoginCode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminLoginCode query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminLoginCode whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminLoginCode whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminLoginCode whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminLoginCode whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminLoginCode whereIpAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminLoginCode whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminLoginCode whereUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminLoginCode whereUserId($value)
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class AdminLoginCode extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'code',
        'ip_address',
        'used',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'used' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a 6-digit code for the given user.
     * Invalidates any previous unused codes for the same user.
     */
    public static function generateFor(User $user, string $ip): self
    {
        // Invalidate previous codes
        static::where('user_id', $user->id)
            ->where('used', false)
            ->update(['used' => true]);

        return static::create([
            'user_id' => $user->id,
            'code' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'ip_address' => $ip,
            'expires_at' => now()->addMinutes(5),
        ]);
    }

    /**
     * Verify a code for the given user. Returns the code record if valid, null otherwise.
     */
    public static function verify(User $user, string $code, string $ip): ?self
    {
        return static::where('user_id', $user->id)
            ->where('code', $code)
            ->where('ip_address', $ip)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();
    }
}
