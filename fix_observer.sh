#!/bin/bash
# Script to fix MySQL privileges and restart the observer daemon

echo "Applying MySQL privileges..."
sudo docker exec -i mysql-local mysql -u root -p'e7RptuJrfiEbSVHcY1gblq3DMSRwxASH' <<EOF
GRANT REPLICATION SLAVE, REPLICATION CLIENT, SUPER, SELECT ON *.* TO 'steve'@'%';
FLUSH PRIVILEGES;
EOF

echo "Restarting observer daemon..."
sudo docker restart steve-observer-daemon

echo "Verifying observer logs..."
sudo docker logs steve-observer-daemon --tail 20
