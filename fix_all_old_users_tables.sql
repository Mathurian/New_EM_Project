-- Fix ALL tables with foreign key constraints pointing to old_users
-- This will fix: auditor_certifications, backup_logs, emcee_scripts, overall_deductions, system_settings

-- Disable foreign key constraints
PRAGMA foreign_keys=OFF;

-- Fix auditor_certifications table
CREATE TABLE auditor_certifications_backup AS SELECT * FROM auditor_certifications;
DROP TABLE auditor_certifications;
CREATE TABLE auditor_certifications (
    id TEXT PRIMARY KEY,
    auditor_id TEXT,
    certified_at TEXT NOT NULL,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (auditor_id) REFERENCES users(id) ON DELETE SET NULL
);
INSERT INTO auditor_certifications SELECT * FROM auditor_certifications_backup;
DROP TABLE auditor_certifications_backup;

-- Fix backup_logs table
CREATE TABLE backup_logs_backup AS SELECT * FROM backup_logs;
DROP TABLE backup_logs;
CREATE TABLE backup_logs (
    id TEXT PRIMARY KEY,
    backup_type TEXT NOT NULL CHECK (backup_type IN ('schema', 'full', 'scheduled')),
    file_path TEXT NOT NULL,
    file_size INTEGER NOT NULL,
    status TEXT NOT NULL CHECK (status IN ('success', 'failed', 'in_progress')),
    created_by TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    error_message TEXT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
INSERT INTO backup_logs SELECT * FROM backup_logs_backup;
DROP TABLE backup_logs_backup;

-- Fix emcee_scripts table
CREATE TABLE emcee_scripts_backup AS SELECT * FROM emcee_scripts;
DROP TABLE emcee_scripts;
CREATE TABLE emcee_scripts (
    id TEXT PRIMARY KEY,
    filename TEXT NOT NULL,
    file_path TEXT NOT NULL,
    is_active INTEGER DEFAULT 1,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    uploaded_by TEXT,
    title TEXT,
    description TEXT,
    file_name TEXT,
    file_size INTEGER,
    uploaded_at TEXT,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);
INSERT INTO emcee_scripts SELECT * FROM emcee_scripts_backup;
DROP TABLE emcee_scripts_backup;

-- Fix overall_deductions table
CREATE TABLE overall_deductions_backup AS SELECT * FROM overall_deductions;
DROP TABLE overall_deductions;
CREATE TABLE overall_deductions (
    id TEXT PRIMARY KEY,
    subcategory_id TEXT NOT NULL,
    contestant_id TEXT NOT NULL,
    amount REAL NOT NULL,
    comment TEXT,
    signature_name TEXT,
    signed_at TEXT,
    created_by TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
    FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
INSERT INTO overall_deductions SELECT * FROM overall_deductions_backup;
DROP TABLE overall_deductions_backup;

-- Fix system_settings table
CREATE TABLE system_settings_backup AS SELECT * FROM system_settings;
DROP TABLE system_settings;
CREATE TABLE system_settings (
    id TEXT PRIMARY KEY,
    setting_key TEXT UNIQUE NOT NULL,
    setting_value TEXT NOT NULL,
    description TEXT,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_by TEXT,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
INSERT INTO system_settings SELECT * FROM system_settings_backup;
DROP TABLE system_settings_backup;

-- Re-enable foreign key constraints
PRAGMA foreign_keys=ON;

-- Test emcee_scripts insert
INSERT INTO emcee_scripts (id, filename, file_path, is_active, created_at, uploaded_by, title, description, file_name, file_size, uploaded_at) 
VALUES ('test-' || strftime('%s', 'now'), 'test.pdf', '/uploads/test.pdf', 1, datetime('now'), '45a63c33a756a437d0d99785a8a444fb', 'Test Script', 'Test Description', 'test.pdf', 1024, datetime('now'));

-- Clean up test record
DELETE FROM emcee_scripts WHERE id LIKE 'test-%';

SELECT 'All tables fixed successfully' as status;
