<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bin_start
 * @property int $bin_end
 * @property int $bin_length
 * @property string|null $issuer_bank_key
 * @property string|null $primary_network
 * @property string|null $secondary_network
 * @property string|null $card_type
 * @property string|null $card_level
 * @property string|null $product_name
 * @property string $country_code
 * @property string $currency
 * @property int $confidence
 * @property string $source
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $last_verified_at
 * @property-read \App\Models\IssuerBank|null $issuerBank
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CardBinRange newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CardBinRange newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CardBinRange query()
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class CardBinRange extends Model
{
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'bin_start', 'bin_end', 'bin_length',
        'issuer_bank_key', 'primary_network', 'secondary_network',
        'card_type', 'card_level', 'product_name',
        'country_code', 'currency',
        'confidence', 'source', 'is_active', 'last_verified_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'bin_start'        => 'integer',
        'bin_end'          => 'integer',
        'bin_length'       => 'integer',
        'confidence'       => 'integer',
        'is_active'        => 'boolean',
        'last_verified_at' => 'datetime',
    ];

    public function issuerBank(): BelongsTo
    {
        return $this->belongsTo(IssuerBank::class, 'issuer_bank_key', 'key');
    }
}
