-- SQL script to fix backup_settings constraint
-- Run this directly on the database to avoid PHP transaction issues

-- First, let's see the current constraint
.schema backup_settings

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

-- Verify the new constraint works
INSERT INTO backup_settings (id, backup_type, enabled, frequency, frequency_value, retention_days) 
VALUES ('test-schema', 'schema', 0, 'minutes', 1, 30);

-- Clean up test record
DELETE FROM backup_settings WHERE id = 'test-schema';

-- Show the new schema
.schema backup_settings

-- Show current data
SELECT * FROM backup_settings;
