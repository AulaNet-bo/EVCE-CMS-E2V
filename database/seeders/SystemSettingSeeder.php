<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SystemSetting::firstOrCreate([], [
            'platform_name' => 'E2V Charging Network',
            'disclaimer_text' => 'Al utilizar esta plataforma, usted acepta los términos y condiciones de uso. E2V no se hace responsable por daños derivados del mal uso de los cargadores.',
            'is_disclaimer_visible' => true,
            'primary_color' => '#4F46E5',
            'secondary_color' => '#10B981',
            'button_color' => '#4F46E5',
            'text_color' => '#111827',
            'font_family' => 'Inter',
        ]);
    }
}
