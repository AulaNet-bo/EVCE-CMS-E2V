<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform_name',
        'logo_path',
        'disclaimer_text',
        'is_disclaimer_visible',
        'primary_color',
        'secondary_color',
        'button_color',
        'text_color',
        'font_family',
    ];

    protected $casts = [
        'is_disclaimer_visible' => 'boolean',
    ];

    /**
     * Get the singleton settings record.
     */
    public static function get(): self
    {
        return self::firstOrCreate([], [
            'platform_name' => 'E2V Charging Network',
            'is_disclaimer_visible' => true,
            'primary_color' => '#4F46E5',
            'secondary_color' => '#10B981',
            'button_color' => '#4F46E5',
            'text_color' => '#111827',
            'font_family' => 'Inter',
        ]);
    }
}
