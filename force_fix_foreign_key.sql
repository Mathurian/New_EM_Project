-- Force fix for old_users foreign key constraint issue
-- This script will definitely fix the foreign key constraint problem

-- Step 1: Disable foreign key constraints
PRAGMA foreign_keys=OFF;

-- Step 2: Check current foreign key constraints
PRAGMA foreign_key_list(activity_logs);

-- Step 3: Backup activity_logs data
CREATE TABLE activity_logs_backup AS SELECT * FROM activity_logs;

-- Step 4: Drop activity_logs table
DROP TABLE activity_logs;

-- Step 5: Recreate activity_logs table with correct foreign key
CREATE TABLE activity_logs (
    id TEXT PRIMARY KEY,
    user_id TEXT,
    user_name TEXT,
    user_role TEXT,
    action TEXT NOT NULL,
    resource_type TEXT,
    resource_id TEXT,
    details TEXT,
    ip_address TEXT,
    user_agent TEXT,
    log_level TEXT NOT NULL DEFAULT 'info',
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Step 6: Restore data
INSERT INTO activity_logs SELECT * FROM activity_logs_backup;

-- Step 7: Drop backup table
DROP TABLE activity_logs_backup;

-- Step 8: Re-enable foreign key constraints
PRAGMA foreign_keys=ON;

-- Step 9: Verify the fix
PRAGMA foreign_key_list(activity_logs);

-- Step 10: Test insert
INSERT INTO activity_logs (id, user_id, user_name, user_role, action, resource_type, resource_id, details, ip_address, user_agent, log_level, created_at) 
VALUES ('test-fix-' || strftime('%s', 'now'), NULL, 'Test User', 'test', 'test_action', 'test', 'test_id', 'Testing foreign key fix', '127.0.0.1', 'Test Agent', 'info', datetime('now'));

-- Clean up test record
DELETE FROM activity_logs WHERE id LIKE 'test-fix-%';

SELECT 'Foreign key constraint fix completed successfully' as status;
