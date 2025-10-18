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

-- Step 4: Check for foreign key constraint issues
-- If foreign key constraints point to old_users, we need to fix them
PRAGMA foreign_key_list(activity_logs);

-- Step 5: If the above shows old_users references, run these commands:
-- (Uncomment the following lines if needed)

-- -- Backup activity_logs data
-- CREATE TABLE activity_logs_backup AS SELECT * FROM activity_logs;

-- -- Drop and recreate activity_logs table with correct foreign key
-- DROP TABLE activity_logs;
-- CREATE TABLE activity_logs (
--     id TEXT PRIMARY KEY,
--     user_id TEXT,
--     user_name TEXT,
--     user_role TEXT,
--     action TEXT NOT NULL,
--     resource_type TEXT,
--     resource_id TEXT,
--     details TEXT,
--     ip_address TEXT,
--     user_agent TEXT,
--     log_level TEXT NOT NULL DEFAULT 'info',
--     created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
-- );

-- -- Restore data
-- INSERT INTO activity_logs SELECT * FROM activity_logs_backup;
-- DROP TABLE activity_logs_backup;

-- Step 6: Re-enable foreign key constraints
PRAGMA foreign_keys=ON;

-- Step 7: Test foreign key constraints
PRAGMA foreign_key_check;

-- Step 8: Show current foreign key constraints
PRAGMA foreign_key_list(activity_logs);
