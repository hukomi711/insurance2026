<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'company_id',
        'sub_type',
        'base_price',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'base_price' => 'decimal:2',
    ];

    /**
     * Get the fixed price for this plan (without VAT).
     */
    public function getBasePriceAttribute($value)
    {
        return (float) $value;
    }
}
