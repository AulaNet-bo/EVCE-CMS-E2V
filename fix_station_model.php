<?php
$file = 'app/Models/Station.php';
$content = file_get_contents($file);

$oldCode = 'return app(\App\Services\SteveDataSource::class)->getLatestChargeBoxStatus($this->charge_box_id) ?? $value;';
$newCode = '$status = app(\App\Services\SteveDataSource::class)->getLatestChargeBoxStatus($this->charge_box_id); return is_object($status) ? ($status->last_heartbeat_timestamp ?? ($status->last_heartbeat ?? $value)) : $value;';

if (strpos($content, 'is_object($status)') === false) {
    $content = str_replace($oldCode, $newCode, $content);
    file_put_contents($file, $content);
    echo "Station Model Fixed successfully.\n";
} else {
    echo "Station Model already fixed or code mismatch.\n";
}
