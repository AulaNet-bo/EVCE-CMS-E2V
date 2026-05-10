<?php

use App\Models\SystemSetting;

$settings = SystemSetting::first();
if ($settings) {
    $settings->nit_requirement_policy = 'required';
    $settings->save();
    echo "SUCCESS: nit_requirement_policy set to required.\n";
} else {
    echo "ERROR: SystemSetting record not found.\n";
}
