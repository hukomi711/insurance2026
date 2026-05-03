<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $key
 * @property string $name_ar
 * @property string $name_en
 * @property string|null $short_name
 * @property string $country_code
 * @property string|null $logo_path
 * @property string|null $brand_color
 * @property string|null $theme
 * @property string|null $website
 * @property bool $is_active
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CardBinRange> $binRanges
 * @property-read int|null $bin_ranges_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IssuerBank newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IssuerBank newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IssuerBank query()
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class IssuerBank extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'key', 'name_ar', 'name_en', 'short_name',
        'country_code', 'logo_path', 'brand_color', 'theme', 'website', 'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function binRanges(): HasMany
    {
        return $this->hasMany(CardBinRange::class, 'issuer_bank_key', 'key');
    }
}
