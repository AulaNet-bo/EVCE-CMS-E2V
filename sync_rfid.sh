#!/bin/bash
# Sync critical files to production docker
FILES=(
    "app/Filament/Resources/RfidTagResource.php"
    "app/Filament/Resources/RfidTagResource/Pages/BulkRfidManager.php"
    "app/Http/Controllers/Api/V1/Mobile/AuthController.php"
)

for FILE in "${FILES[@]}"; do
    echo "Syncing $FILE..."
    sudo docker cp "/home/Usuario/steve-cms/$FILE" "steve-cms-app:/app/$FILE"
done

echo "Sync complete. Running optimize:clear..."
sudo docker exec steve-cms-app php artisan optimize:clear
echo "Done."
