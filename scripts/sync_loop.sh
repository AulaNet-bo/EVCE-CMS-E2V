#!/usr/bin/env bash
set -euo pipefail

cd /app

while true; do
  php artisan steve:sync-tags --limit=500 || true
  php artisan steve:sync-status || true
  php artisan steve:sync-sessions --limit=500 || true
  php artisan steve:monitor-transactions || true
  sleep 5
done
