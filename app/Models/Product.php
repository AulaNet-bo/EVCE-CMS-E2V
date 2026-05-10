<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'siat_product_code',
        'internal_code',
        'price',
        'unit_of_measure',
        'type',
        'is_active',
        'category',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function rfidTags()
    {
        return $this->hasMany(RfidTag::class);
    }

    public function chargingSessions()
    {
        return $this->hasMany(ChargingSession::class);
    }
}
