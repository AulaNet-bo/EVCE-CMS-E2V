<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;

class DisclaimerController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::get();

        if (!$settings->is_disclaimer_visible) {
            abort(404, 'Disclaimer no disponible');
        }

        return view('public.disclaimer', [
            'settings' => $settings
        ]);
    }
}
