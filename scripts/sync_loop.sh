#!/usr/bin/env bash
set -euo pipefail

cd /app

# Tunables
FAST_INTERVAL=2      # sessions/status refresh
MONITOR_INTERVAL=2   # pricing/status materialization
TAGS_INTERVAL=30     # tags change less often

last_tags=0
last_monitor=0

while true; do
  now=$(date +%s)

  # Fast path (dashboard/session freshness)
  php artisan steve:sync-status || true
  php artisan steve:sync-sessions --limit=500 || true

  # Business materialization
  if (( now - last_monitor >= MONITOR_INTERVAL )); then
    php artisan steve:monitor-transactions || true
    last_monitor=$now
  fi

  # Slow path
  if (( now - last_tags >= TAGS_INTERVAL )); then
    php artisan steve:sync-tags --limit=500 || true
    last_tags=$now
  fi

  sleep "$FAST_INTERVAL"
done
