-- Comprehensive fix for old_users table and foreign key issues
-- This script resolves the "no such table: main.old_users" error

-- Step 1: Disable foreign key constraints temporarily
PRAGMA foreign_keys=OFF;

-- Step 2: Drop any leftover migration tables
DROP TABLE IF EXISTS old_users;
DROP TABLE IF EXISTS users_new;
DROP TABLE IF EXISTS users_backup;
DROP TABLE IF EXISTS users_temp;
DROP TABLE IF EXISTS users_old;

-- Step 3: Verify the main users table is intact
SELECT 'Users table count: ' || COUNT(*) as status FROM users;

-- Step 4: Re-enable foreign key constraints
PRAGMA foreign_keys=ON;

-- Step 5: Test foreign key constraints
PRAGMA foreign_key_check;

-- Step 6: Show current foreign key constraints
PRAGMA foreign_key_list(activity_logs);
