<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'brand',
        'model',
        'plate',
        'vin',
        'battery_capacity',
    ];

    protected $casts = [
        'battery_capacity' => 'float',
    ];

    /**
     * Get the user that owns the vehicle.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mutator to clean and format the plate to uppercase and strip non-alphanumeric characters.
     */
    public function setPlateAttribute($value)
    {
        $cleaned = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $value));
        $this->attributes['plate'] = $cleaned;
    }
}
