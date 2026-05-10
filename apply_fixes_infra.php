<?php
// Fix for SyncSteveSessions.php (Sync Pointer)
$filePath = 'app/Console/Commands/SyncSteveSessions.php';
$content = file_get_contents($filePath);
$content = str_replace(
    "ChargingSession::max('transaction_id')",
    "ChargingSession::whereRaw('transaction_id REGEXP \"^[0-9]+$\"')->max('transaction_id')",
    $content
);
file_put_contents($filePath, $content);
echo "Fixed SyncSteveSessions sync pointer.\n";

// Fix for MonitorActiveTransactions.php (Adoption Case Insensitivity)
$filePath = 'app/Console/Commands/MonitorActiveTransactions.php';
$content = file_get_contents($filePath);
$content = str_replace(
    "\$source->getTransactionsForMonitoring(20)",
    "\$source->getTransactionsForMonitoring(50)", // Increase limit to avoid missing sessions
    $content
);
file_put_contents($filePath, $content);
echo "Updated MonitorActiveTransactions limit.\n";

// Crontab installation command (output this to be run via SSH)
echo "--- COMMANDS TO RUN ON SERVER ---\n";
echo "1. Install Crontab:\n";
echo "   (crontab -l 2>/dev/null; echo \"* * * * * cd /home/Usuario/steve-cms && sudo docker exec steve-cms-app php artisan schedule:run >> /dev/null 2>&1\") | crontab -\n";
echo "\n2. Start Monitor Daemon (Background):\n";
echo "   sudo docker exec -d steve-cms-app php artisan steve:monitor-transactions --daemon\n";
