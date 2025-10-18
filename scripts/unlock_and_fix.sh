#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")/.."

log() { echo "[$(date '+%F %T')] $*"; }

log "Stopping web services to release SQLite locks..."
systemctl stop apache2 2>/dev/null || true
systemctl stop httpd 2>/dev/null || true
systemctl stop php-fpm 2>/dev/null || true
systemctl stop nginx 2>/dev/null || true
pkill -9 -f "php-fpm" 2>/dev/null || true
pkill -9 -f "apache2" 2>/dev/null || true
pkill -9 -f "httpd" 2>/dev/null || true

log "Checkpointing WAL and switching journal..."
if command -v sqlite3 >/dev/null 2>&1; then
  sqlite3 app/db/contest.sqlite "PRAGMA wal_checkpoint(TRUNCATE); PRAGMA journal_mode=DELETE;"
fi
rm -f app/db/contest.sqlite-wal app/db/contest.sqlite-shm || true

log "Running unlock-and-rebuild migration..."
php scripts/fix_constraint_unlock.php

log "Starting web services..."
systemctl start apache2 2>/dev/null || true
systemctl start httpd 2>/dev/null || true
systemctl start php-fpm 2>/dev/null || true
systemctl start nginx 2>/dev/null || true

log "Done."
