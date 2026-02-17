<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tariff extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'currency',
        'cost_price_kwh', // Base Utility Cost (Deprecated in favor of blocks, but kept for legacy)
        'price_session',
        'free_minutes',
        // Block 1
        'b1_start', 'b1_end', 'b1_price_kwh', 'b1_cost_kwh', 'b1_price_min',
        // Block 2
        'b2_start', 'b2_end', 'b2_price_kwh', 'b2_cost_kwh', 'b2_price_min',
        // Block 3
        'b3_start', 'b3_end', 'b3_price_kwh', 'b3_cost_kwh', 'b3_price_min',
        // Block 4
        'b4_start', 'b4_end', 'b4_price_kwh', 'b4_cost_kwh', 'b4_price_min',
    ];

    public function stations(): HasMany
    {
        return $this->hasMany(Station::class);
    }
}
