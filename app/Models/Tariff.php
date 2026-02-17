<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Tariff extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (Tariff $tariff) {
            if ($tariff->isExpired()) {
                throw ValidationException::withMessages([
                    'tariff' => 'Expired tariffs cannot be modified.',
                ]);
            }

            if ($tariff->hasBeenUsed()) {
                $dirty = array_keys($tariff->getDirty());
                $allowed = ['valid_until', 'updated_at'];
                $blocked = array_values(array_diff($dirty, $allowed));

                if (!empty($blocked)) {
                    throw ValidationException::withMessages([
                        'tariff' => 'This tariff already has historical usage. Only "valid until" can be changed.',
                    ]);
                }
            }
        });

        static::deleting(function (Tariff $tariff) {
            if ($tariff->isExpired()) {
                throw ValidationException::withMessages([
                    'tariff' => 'Expired tariffs cannot be deleted.',
                ]);
            }

            if ($tariff->hasBeenUsed()) {
                throw ValidationException::withMessages([
                    'tariff' => 'This tariff has historical usage and cannot be deleted.',
                ]);
            }
        });
    }

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
    ];

    protected $fillable = [
        'name',
        'currency',
        'cost_price_kwh', // Base Utility Cost (Deprecated in favor of blocks, but kept for legacy)
        'price_session',
        'free_minutes',
        'valid_from',
        'valid_until',
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

    public function chargingSessions(): HasMany
    {
        return $this->hasMany(ChargingSession::class, 'tariff_id');
    }

    public function hasBeenUsed(): bool
    {
        return $this->chargingSessions()->exists();
    }

    public function isExpired(): bool
    {
        return $this->valid_until !== null && now()->greaterThan($this->valid_until);
    }
}
