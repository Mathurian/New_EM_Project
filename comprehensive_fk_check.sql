-- Comprehensive check for ALL foreign key constraints referencing old_users
-- This will find any remaining references to the old_users table

-- Check all tables for foreign key constraints
SELECT 'Tables with foreign key constraints:' as info;

-- Get all tables
SELECT name as table_name FROM sqlite_master WHERE type='table' ORDER BY name;

-- Check foreign key constraints for each table
-- (Note: SQLite doesn't have a way to check all foreign keys at once, so we need to check each table individually)

-- Check activity_logs
SELECT 'activity_logs foreign keys:' as info;
PRAGMA foreign_key_list(activity_logs);

-- Check emcee_scripts
SELECT 'emcee_scripts foreign keys:' as info;
PRAGMA foreign_key_list(emcee_scripts);

-- Check users
SELECT 'users foreign keys:' as info;
PRAGMA foreign_key_list(users);

-- Check judges
SELECT 'judges foreign keys:' as info;
PRAGMA foreign_key_list(judges);

-- Check contestants
SELECT 'contestants foreign keys:' as info;
PRAGMA foreign_key_list(contestants);

-- Check categories
SELECT 'categories foreign keys:' as info;
PRAGMA foreign_key_list(categories);

-- Check subcategories
SELECT 'subcategories foreign keys:' as info;
PRAGMA foreign_key_list(subcategories);

-- Check scores
SELECT 'scores foreign keys:' as info;
PRAGMA foreign_key_list(scores);

-- Check judge_certifications
SELECT 'judge_certifications foreign keys:' as info;
PRAGMA foreign_key_list(judge_certifications);

-- Check tally_master_certifications
SELECT 'tally_master_certifications foreign keys:' as info;
PRAGMA foreign_key_list(tally_master_certifications);

-- Check auditor_certifications
SELECT 'auditor_certifications foreign keys:' as info;
PRAGMA foreign_key_list(auditor_certifications);

-- Check overall_deductions
SELECT 'overall_deductions foreign keys:' as info;
PRAGMA foreign_key_list(overall_deductions);

-- Check system_settings
SELECT 'system_settings foreign keys:' as info;
PRAGMA foreign_key_list(system_settings);

-- Check backup_logs
SELECT 'backup_logs foreign keys:' as info;
PRAGMA foreign_key_list(backup_logs);

-- Check backup_settings
SELECT 'backup_settings foreign keys:' as info;
PRAGMA foreign_key_list(backup_settings);

-- Check for any views referencing old_users
SELECT 'Views referencing old_users:' as info;
SELECT name, sql FROM sqlite_master WHERE type='view' AND sql LIKE '%old_users%';

-- Check for any triggers referencing old_users
SELECT 'Triggers referencing old_users:' as info;
SELECT name, sql FROM sqlite_master WHERE type='trigger' AND sql LIKE '%old_users%';

-- Check for any indexes referencing old_users
SELECT 'Indexes referencing old_users:' as info;
SELECT name, sql FROM sqlite_master WHERE type='index' AND sql LIKE '%old_users%';

-- Test insert into emcee_scripts to see the exact error
SELECT 'Testing emcee_scripts insert...' as info;
INSERT INTO emcee_scripts (id, filename, file_path, is_active, created_at, uploaded_by, title, description, file_name, file_size, uploaded_at) 
VALUES ('test-' || strftime('%s', 'now'), 'test.pdf', '/uploads/test.pdf', 1, datetime('now'), '45a63c33a756a437d0d99785a8a444fb', 'Test Script', 'Test Description', 'test.pdf', 1024, datetime('now'));

-- Clean up test record
DELETE FROM emcee_scripts WHERE id LIKE 'test-%';

SELECT 'Foreign key constraint check complete' as status;
