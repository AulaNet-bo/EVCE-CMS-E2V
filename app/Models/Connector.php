<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Connector extends Model
{
    use HasFactory;

    protected $fillable = [
        'station_id',
        'connector_id',
        'type',
        'max_power_kw',
        'status',
        'connector_pk'
    ];

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class);
    }

    /**
     * Get human readable connector type.
     */
    public function getTypeNameAttribute(): string
    {
        $types = [
            'CCS2' => 'CCS Type 2',
            'GBT' => 'GB/T (DC)',
            'CHAdeMO' => 'CHAdeMO',
            'Type2' => 'Type 2 (AC)',
            'Type1' => 'Type 1 (J1772)',
        ];

        return $types[$this->type] ?? $this->type ?? 'Unknown';
    }
}
