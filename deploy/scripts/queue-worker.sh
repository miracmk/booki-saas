#!/usr/bin/env bash
# BooKi - Notification & Job Queue Worker (2026-09-19)
#
# Drains the jobs queue for all tenants and triggers appointment reminders.
# Usage:
#   * * * * * /opt/ki-ecosystem/ki-reservation-src/deploy/scripts/queue-worker.sh >> /var/log/booki-jobs.log 2>&1
#

set -uo pipefail

APP_CONTAINER="${APP_CONTAINER:-ki-reservation-app}"

if ! docker ps --format '{{.Names}}' | grep -q "^${APP_CONTAINER}$"; then
    echo "[$(date -u +'%Y-%m-%d %H:%M:%SZ')] Container ${APP_CONTAINER} is not running, skipping queue drain."
    exit 0
fi

# 1. Process asynchronous jobs queue
docker exec -w /var/www/html "${APP_CONTAINER}" php index.php console process_jobs default 50

# 2. If called with --reminders or at 09:00 UTC, run appointment reminders
if [[ "${1:-}" == "--reminders" ]]; then
    docker exec -w /var/www/html "${APP_CONTAINER}" php index.php console send_reminders 24
fi
