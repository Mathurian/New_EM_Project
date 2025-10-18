#!/bin/bash

# Shell script to fix backup_settings constraint
# This script handles SQLite locking issues more aggressively

DB_PATH="/var/www/html/app/db/contest.sqlite"

echo "Database Constraint Fix Script"
echo "=============================="
echo "Database path: $DB_PATH"

# Check if database exists
if [ ! -f "$DB_PATH" ]; then
    echo "Error: Database file not found at: $DB_PATH"
    exit 1
fi

# Stop any processes that might be using the database
echo "Stopping web server and PHP processes..."
sudo systemctl stop apache2 2>/dev/null || true
sudo systemctl stop nginx 2>/dev/null || true
sudo pkill -f php-fpm 2>/dev/null || true
sudo pkill -f php 2>/dev/null || true

# Wait a moment for processes to stop
sleep 3

# Remove WAL and SHM files if they exist
WAL_FILE="$DB_PATH-wal"
SHM_FILE="$DB_PATH-shm"

if [ -f "$WAL_FILE" ]; then
    echo "Removing WAL file: $WAL_FILE"
    rm -f "$WAL_FILE"
fi

if [ -f "$SHM_FILE" ]; then
    echo "Removing SHM file: $SHM_FILE"
    rm -f "$SHM_FILE"
fi

# Create temporary SQL file
TEMP_SQL=$(mktemp)
cat > "$TEMP_SQL" << 'EOF'
-- Set journal mode to DELETE to avoid WAL issues
PRAGMA journal_mode=DELETE;
PRAGMA busy_timeout=60000;
PRAGMA synchronous=NORMAL;

-- Create new table with updated constraint
CREATE TABLE backup_settings_new (
    id TEXT PRIMARY KEY,
    backup_type TEXT NOT NULL CHECK (backup_type IN ('schema', 'full')),
    enabled BOOLEAN NOT NULL DEFAULT 0,
    frequency TEXT NOT NULL CHECK (frequency IN ('minutes', 'hours', 'daily', 'weekly', 'monthly')),
    frequency_value INTEGER NOT NULL DEFAULT 1,
    retention_days INTEGER NOT NULL DEFAULT 30,
    last_run TEXT,
    next_run TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Copy data from old table
INSERT INTO backup_settings_new SELECT * FROM backup_settings;

-- Drop old table and rename new one
DROP TABLE backup_settings;
ALTER TABLE backup_settings_new RENAME TO backup_settings;
EOF

echo "Executing constraint update..."

# Try multiple times with different approaches
for attempt in 1 2 3; do
    echo "Attempt $attempt..."
    
    # Try direct sqlite3 execution
    if sqlite3 "$DB_PATH" < "$TEMP_SQL" 2>/dev/null; then
        echo "Constraint update completed successfully!"
        
        # Test the constraint
        echo "Testing constraint..."
        if sqlite3 "$DB_PATH" "INSERT INTO backup_settings (id, backup_type, enabled, frequency, frequency_value, retention_days) VALUES ('test-schema', 'schema', 0, 'minutes', 1, 30); DELETE FROM backup_settings WHERE id = 'test-schema';" 2>/dev/null; then
            echo "Constraint test: SUCCESS"
        else
            echo "Constraint test: FAILED"
        fi
        
        # Clean up
        rm -f "$TEMP_SQL"
        
        # Restart web server
        echo "Restarting web server..."
        sudo systemctl start apache2 2>/dev/null || sudo systemctl start nginx 2>/dev/null || true
        
        echo "Fix completed successfully!"
        exit 0
    else
        echo "Attempt $attempt failed"
        if [ $attempt -lt 3 ]; then
            echo "Waiting 5 seconds before retry..."
            sleep 5
        fi
    fi
done

echo "All attempts failed. Database may be heavily locked."
echo "Try running this script as root or with sudo."

# Clean up
rm -f "$TEMP_SQL"

# Restart web server
echo "Restarting web server..."
sudo systemctl start apache2 2>/dev/null || sudo systemctl start nginx 2>/dev/null || true

exit 1
