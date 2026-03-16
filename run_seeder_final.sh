#!/bin/bash
sudo docker exec steve-cms-app php artisan tinker --execute "require '/app/seed_las_palmas.php';"
