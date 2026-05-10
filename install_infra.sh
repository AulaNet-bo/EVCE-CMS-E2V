#!/bin/bash
(crontab -l 2>/dev/null; echo "* * * * * cd /home/Usuario/steve-cms && sudo docker exec steve-cms-app php artisan schedule:run >> /dev/null 2>&1") | crontab -
sudo docker exec -d steve-cms-app php artisan steve:monitor-transactions --daemon
echo "Infrastructure components installed and started."
