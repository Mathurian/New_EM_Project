#!/bin/bash
set -euo pipefail

# Offline migration: copy DB, migrate on the copy, atomically swap back
# Usage: run as root in project root: ./scripts/offline_copy_migrate.sh

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DB_DIR="$PROJECT_ROOT/app/db"
SRC_DB="$DB_DIR/contest.sqlite"
WORK_DB="$DB_DIR/contest_work.sqlite"
BACKUP_DB="$DB_DIR/contest.sqlite.bak_$(date +%Y%m%d_%H%M%S)"

log() { echo "[$(date '+%F %T')] $*"; }

cd "$PROJECT_ROOT"

log "Stopping services..."
systemctl stop apache2 2>/dev/null || true
systemctl stop httpd 2>/dev/null || true
systemctl stop php-fpm 2>/dev/null || true
systemctl stop nginx 2>/dev/null || true
pkill -9 -f "apache2|httpd|php-fpm|php .*public/index.php" 2>/dev/null || true

log "Preparing working copy..."
cp -f "$SRC_DB" "$WORK_DB"
chmod 664 "$WORK_DB"

log "Running migration on working copy..."
sqlite3 "$WORK_DB" <<'SQL'
PRAGMA foreign_keys=OFF;
PRAGMA journal_mode=DELETE;
BEGIN IMMEDIATE;

CREATE TABLE IF NOT EXISTS judge_certifications_new (
  id TEXT PRIMARY KEY,
  subcategory_id TEXT NOT NULL,
  contestant_id TEXT NOT NULL,
  judge_id TEXT NOT NULL,
  signature_name TEXT NOT NULL,
  certified_at TEXT NOT NULL,
  UNIQUE (subcategory_id, contestant_id, judge_id)
);

INSERT OR IGNORE INTO judge_certifications_new
  (id, subcategory_id, contestant_id, judge_id, signature_name, certified_at)
SELECT
  COALESCE(id, lower(hex(randomblob(16)))),
  subcategory_id,
  contestant_id,
  judge_id,
  signature_name,
  certified_at
FROM judge_certifications
WHERE contestant_id IS NOT NULL;

INSERT OR IGNORE INTO judge_certifications_new
  (id, subcategory_id, contestant_id, judge_id, signature_name, certified_at)
SELECT
  lower(hex(randomblob(16))),
  jc.subcategory_id,
  sc.contestant_id,
  jc.judge_id,
  jc.signature_name,
  jc.certified_at
FROM judge_certifications jc
JOIN subcategory_contestants sc
  ON sc.subcategory_id = jc.subcategory_id
WHERE jc.contestant_id IS NULL;

DROP TABLE judge_certifications;
ALTER TABLE judge_certifications_new RENAME TO judge_certifications;

CREATE UNIQUE INDEX IF NOT EXISTS judge_certifications_unique
  ON judge_certifications (subcategory_id, contestant_id, judge_id);

COMMIT;
PRAGMA foreign_keys=ON;
SQL

log "Backing up and swapping databases..."
mv "$SRC_DB" "$BACKUP_DB"
mv "$WORK_DB" "$SRC_DB"
rm -f "$DB_DIR/contest.sqlite-wal" "$DB_DIR/contest.sqlite-shm" || true
chown -R www-data:www-data "$DB_DIR" 2>/dev/null || true
chmod 775 "$DB_DIR" 2>/dev/null || true
chmod 664 "$SRC_DB" 2>/dev/null || true

log "Starting services..."
systemctl start apache2 2>/dev/null || true
systemctl start httpd 2>/dev/null || true
systemctl start php-fpm 2>/dev/null || true
systemctl start nginx 2>/dev/null || true

log "Done. Backup at: $BACKUP_DB"
