#!/bin/bash
# Sync critical files to production docker
FILES=(
    "app/Models/Tariff.php"
    "app/Console/Commands/MonitorActiveTransactions.php"
    "app/Console/Commands/SyncSteveSessions.php"
    "app/Services/BillingService.php"
    "app/Services/LibelulaPaymentService.php"
    "app/Filament/Resources/WalletResource.php"
    "app/Filament/Resources/RfidTagResource/Pages/BulkRfidManager.php"
)

for FILE in "${FILES[@]}"; do
    echo "Syncing $FILE..."
    sudo docker cp "/home/Usuario/steve-cms/$FILE" "steve-cms-app:/app/$FILE"
done

echo "Sync complete. Running optimize:clear..."
sudo docker exec steve-cms-app php artisan optimize:clear
echo "Done."
