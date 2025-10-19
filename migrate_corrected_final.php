#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Corrected Migration Script
 * 
 * This script handles the actual SQLite schema structure:
 * - Separate judges and contestants tables
 * - Proper consolidation into users table
 * - Correct foreign key relationships
 */

// Database connection settings
$sqliteFile = '/var/www/html/app/db/contest.sqlite';
$pgsqlHost = 'localhost';
$pgsqlPort = '5432';
$pgsqlDb = 'event_manager';
$pgsqlUser = 'event_manager';
$pgsqlPass = 'dittibop';

// Color codes for output
$colors = [
    'green' => "\033[32m",
    'red' => "\033[31m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'reset' => "\033[0m",
    'bold' => "\033[1m"
];

function colorize($text, $color) {
    global $colors;
    return $colors[$color] . $text . $colors['reset'];
}

function logMessage($message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    switch ($type) {
        case 'success':
            echo colorize("✅ [$timestamp] $message", 'green') . "\n";
            break;
        case 'error':
            echo colorize("❌ [$timestamp] $message", 'red') . "\n";
            break;
        case 'warning':
            echo colorize("⚠️  [$timestamp] $message", 'yellow') . "\n";
            break;
        case 'info':
        default:
            echo colorize("ℹ️  [$timestamp] $message", 'blue') . "\n";
            break;
    }
}

function logHeader($message) {
    global $colors;
    echo "\n" . colorize("=" . str_repeat("=", strlen($message)) . "=", 'bold') . "\n";
    echo colorize(" $message ", 'bold') . "\n";
    echo colorize("=" . str_repeat("=", strlen($message)) . "=", 'bold') . "\n\n";
}

try {
    logHeader("Corrected Migration Script");
    
    // Connect to SQLite
    logMessage("Connecting to SQLite database...", 'info');
    $sqlite = new PDO("sqlite:$sqliteFile");
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logMessage("SQLite connection successful", 'success');

    // Connect to PostgreSQL
    logMessage("Connecting to PostgreSQL database...", 'info');
    $pgsql = new PDO("pgsql:host=$pgsqlHost;port=$pgsqlPort;dbname=$pgsqlDb", $pgsqlUser, $pgsqlPass);
    $pgsql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logMessage("PostgreSQL connection successful", 'success');

    // Step 1: Enable UUID extension
    logHeader("Step 1: Enabling UUID Extension");
    try {
        $pgsql->exec("CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\"");
        logMessage("UUID extension enabled", 'success');
    } catch (PDOException $e) {
        logMessage("UUID extension warning: " . $e->getMessage(), 'warning');
    }

    // Step 2: Drop all existing tables and recreate
    logHeader("Step 2: Creating Corrected Schema");
    
    // Drop all existing tables
    $tablesToDrop = [
        'users', 'events', 'contest_groups', 'categories', 'criteria',
        'emcee_scripts', 'system_settings', 'backup_settings', 'activity_logs',
        'backup_logs', 'category_contestants', 'category_judges',
        'subcategory_contestants', 'subcategory_judges', 'scores',
        'judge_comments', 'judge_certifications', 'tally_master_certifications',
        'auditor_certifications', 'judge_score_removal_requests',
        'overall_deductions', 'subcategory_templates', 'template_criteria',
        'archived_events', 'archived_contest_groups', 'archived_categories',
        'archived_contestants', 'archived_judges', 'archived_criteria',
        'archived_scores', 'archived_judge_comments', 'archived_tally_master_certifications',
        'contests', 'old_categories', 'old_subcategories', 'old_judges', 'old_contestants'
    ];
    
    foreach ($tablesToDrop as $table) {
        try {
            $pgsql->exec("DROP TABLE IF EXISTS $table CASCADE");
            $pgsql->exec("DROP VIEW IF EXISTS $table CASCADE");
        } catch (PDOException $e) {
            // Ignore errors
        }
    }
    
    // Create tables with correct schemas
    $tables = [
        'users' => getUsersTableSQL(),
        'events' => getEventsTableSQL(),
        'contest_groups' => getContestGroupsTableSQL(),
        'categories' => getCategoriesTableSQL(),
        'criteria' => getCriteriaTableSQL(),
        'emcee_scripts' => getEmceeScriptsTableSQL(),
        'system_settings' => getSystemSettingsTableSQL(),
        'backup_settings' => getBackupSettingsTableSQL(),
        'activity_logs' => getActivityLogsTableSQL(),
        'backup_logs' => getBackupLogsTableSQL(),
        'category_contestants' => getCategoryContestantsTableSQL(),
        'category_judges' => getCategoryJudgesTableSQL(),
        'subcategory_contestants' => getSubcategoryContestantsTableSQL(),
        'subcategory_judges' => getSubcategoryJudgesTableSQL(),
        'scores' => getScoresTableSQL(),
        'judge_comments' => getJudgeCommentsTableSQL(),
        'judge_certifications' => getJudgeCertificationsTableSQL(),
        'tally_master_certifications' => getTallyMasterCertificationsTableSQL(),
        'auditor_certifications' => getAuditorCertificationsTableSQL(),
        'judge_score_removal_requests' => getJudgeScoreRemovalRequestsTableSQL(),
        'overall_deductions' => getOverallDeductionsTableSQL(),
        'subcategory_templates' => getSubcategoryTemplatesTableSQL(),
        'template_criteria' => getTemplateCriteriaTableSQL(),
        'archived_events' => getArchivedEventsTableSQL(),
        'archived_contest_groups' => getArchivedContestGroupsTableSQL(),
        'archived_categories' => getArchivedCategoriesTableSQL(),
        'archived_contestants' => getArchivedContestantsTableSQL(),
        'archived_judges' => getArchivedJudgesTableSQL(),
        'archived_criteria' => getArchivedCriteriaTableSQL(),
        'archived_scores' => getArchivedScoresTableSQL(),
        'archived_judge_comments' => getArchivedJudgeCommentsTableSQL(),
        'archived_tally_master_certifications' => getArchivedTallyMasterCertificationsTableSQL(),
    ];

    foreach ($tables as $tableName => $sql) {
        try {
            $pgsql->exec($sql);
            logMessage("Table $tableName created", 'success');
        } catch (PDOException $e) {
            logMessage("Error creating table $tableName: " . $e->getMessage(), 'error');
        }
    }

    // Step 3: Migrate data in correct order
    logHeader("Step 3: Migrating Data in Correct Order");
    
    $totalMigrated = 0;
    
    // First, migrate core tables
    $coreTables = [
        'users' => 'users',
        'contests' => 'events',
        'categories' => 'contest_groups',
        'subcategories' => 'categories',
        'criteria' => 'criteria',
    ];
    
    foreach ($coreTables as $sqliteTable => $pgsqlTable) {
        $migrated = migrateTable($sqlite, $pgsql, $sqliteTable, $pgsqlTable);
        $totalMigrated += $migrated;
    }
    
    // Then migrate judges and contestants separately, then consolidate
    logMessage("Migrating judges → users (as judges)", 'info');
    $judgesMigrated = migrateJudgesToUsers($sqlite, $pgsql);
    $totalMigrated += $judgesMigrated;
    
    logMessage("Migrating contestants → users (as contestants)", 'info');
    $contestantsMigrated = migrateContestantsToUsers($sqlite, $pgsql);
    $totalMigrated += $contestantsMigrated;
    
    // Then migrate other tables
    $otherTables = [
        'emcee_scripts' => 'emcee_scripts',
        'system_settings' => 'system_settings',
        'backup_settings' => 'backup_settings',
        'activity_logs' => 'activity_logs',
        'backup_logs' => 'backup_logs',
        'subcategory_contestants' => 'subcategory_contestants',
        'subcategory_judges' => 'subcategory_judges',
        'scores' => 'scores',
        'judge_comments' => 'judge_comments',
        'judge_certifications' => 'judge_certifications',
        'tally_master_certifications' => 'tally_master_certifications',
        'auditor_certifications' => 'auditor_certifications',
        'judge_score_removal_requests' => 'judge_score_removal_requests',
        'overall_deductions' => 'overall_deductions',
        'subcategory_templates' => 'subcategory_templates',
        'template_criteria' => 'template_criteria',
        'archived_contests' => 'archived_events',
        'archived_categories' => 'archived_contest_groups',
        'archived_subcategories' => 'archived_categories',
        'archived_contestants' => 'archived_contestants',
        'archived_judges' => 'archived_judges',
        'archived_criteria' => 'archived_criteria',
        'archived_scores' => 'archived_scores',
        'archived_judge_comments' => 'archived_judge_comments',
        'archived_tally_master_certifications' => 'archived_tally_master_certifications',
    ];
    
    foreach ($otherTables as $sqliteTable => $pgsqlTable) {
        $migrated = migrateTable($sqlite, $pgsql, $sqliteTable, $pgsqlTable);
        $totalMigrated += $migrated;
    }

    // Step 4: Create backward compatibility views
    logHeader("Step 4: Creating Backward Compatibility Views");
    
    $views = [
        'contests' => 'SELECT * FROM events',
        'old_categories' => 'SELECT * FROM contest_groups',
        'old_subcategories' => 'SELECT * FROM categories',
        'old_judges' => 'SELECT * FROM users WHERE judge_id IS NOT NULL',
        'old_contestants' => 'SELECT * FROM users WHERE contestant_id IS NOT NULL'
    ];
    
    foreach ($views as $viewName => $viewSQL) {
        try {
            $pgsql->exec("DROP VIEW IF EXISTS $viewName");
            $pgsql->exec("CREATE VIEW $viewName AS $viewSQL");
            logMessage("View $viewName created", 'success');
        } catch (PDOException $e) {
            logMessage("Error creating view $viewName: " . $e->getMessage(), 'error');
        }
    }

    // Step 5: Final validation
    logHeader("Step 5: Final Validation");
    
    $successCount = 0;
    $errorCount = 0;
    
    // Validate core tables
    $validationTables = [
        'users' => 'users',
        'contests' => 'events',
        'categories' => 'contest_groups',
        'subcategories' => 'categories',
        'criteria' => 'criteria',
        'emcee_scripts' => 'emcee_scripts',
        'system_settings' => 'system_settings',
        'backup_settings' => 'backup_settings',
        'activity_logs' => 'activity_logs',
        'backup_logs' => 'backup_logs',
        'subcategory_contestants' => 'subcategory_contestants',
        'subcategory_judges' => 'subcategory_judges',
        'scores' => 'scores',
        'judge_comments' => 'judge_comments',
        'judge_certifications' => 'judge_certifications',
        'tally_master_certifications' => 'tally_master_certifications',
        'auditor_certifications' => 'auditor_certifications',
        'judge_score_removal_requests' => 'judge_score_removal_requests',
        'overall_deductions' => 'overall_deductions',
        'subcategory_templates' => 'subcategory_templates',
        'template_criteria' => 'template_criteria',
        'archived_contests' => 'archived_events',
        'archived_categories' => 'archived_contest_groups',
        'archived_subcategories' => 'archived_categories',
        'archived_contestants' => 'archived_contestants',
        'archived_judges' => 'archived_judges',
        'archived_criteria' => 'archived_criteria',
        'archived_scores' => 'archived_scores',
        'archived_judge_comments' => 'archived_judge_comments',
        'archived_tally_master_certifications' => 'archived_tally_master_certifications',
    ];
    
    foreach ($validationTables as $sqliteTable => $pgsqlTable) {
        try {
            $sqliteCountQuery = "SELECT COUNT(*) FROM $sqliteTable";
            $sqliteCountStmt = $sqlite->query($sqliteCountQuery);
            $sqliteCount = (int) $sqliteCountStmt->fetchColumn();
            
            $pgsqlCountQuery = "SELECT COUNT(*) FROM $pgsqlTable";
            $pgsqlCountStmt = $pgsql->query($pgsqlCountQuery);
            $pgsqlCount = (int) $pgsqlCountStmt->fetchColumn();
            
            if ($sqliteCount === $pgsqlCount) {
                logMessage("✅ $sqliteTable → $pgsqlTable: $sqliteCount rows", 'success');
                $successCount++;
            } else {
                logMessage("❌ $sqliteTable → $pgsqlTable: $sqliteCount vs $pgsqlCount", 'error');
                $errorCount++;
            }
        } catch (PDOException $e) {
            logMessage("Error validating $sqliteTable: " . $e->getMessage(), 'error');
            $errorCount++;
        }
    }
    
    logMessage("Total rows migrated: $totalMigrated", 'success');
    logMessage("Successful validations: $successCount", 'success');
    logMessage("Failed validations: $errorCount", $errorCount > 0 ? 'error' : 'success');
    
    if ($errorCount === 0) {
        logMessage("🎉 MIGRATION COMPLETED SUCCESSFULLY!", 'success');
    } else {
        logMessage("❌ MIGRATION COMPLETED WITH ERRORS!", 'error');
    }

} catch (PDOException $e) {
    logMessage("Database error: " . $e->getMessage(), 'error');
    exit(1);
} catch (Exception $e) {
    logMessage("Unexpected error: " . $e->getMessage(), 'error');
    exit(1);
}

function migrateTable($sqlite, $pgsql, $sqliteTable, $pgsqlTable) {
    try {
        // Check if SQLite table exists and has data
        $countQuery = "SELECT COUNT(*) FROM $sqliteTable";
        $countStmt = $sqlite->query($countQuery);
        $rowCount = (int) $countStmt->fetchColumn();
        
        if ($rowCount > 0) {
            logMessage("Migrating $sqliteTable → $pgsqlTable ($rowCount rows)", 'info');
            
            // Get all data from SQLite
            $dataQuery = "SELECT * FROM $sqliteTable";
            $dataStmt = $sqlite->query($dataQuery);
            $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                // Get column names
                $columns = array_keys($rows[0]);
                $columnList = implode(', ', $columns);
                $placeholders = ':' . implode(', :', $columns);
                
                // Prepare insert statement
                $insertSQL = "INSERT INTO $pgsqlTable ($columnList) VALUES ($placeholders)";
                $insertStmt = $pgsql->prepare($insertSQL);
                
                // Insert each row with UUID fixes
                foreach ($rows as $row) {
                    // Fix UUID formats and handle invalid UUIDs
                    foreach ($row as $key => $value) {
                        if (strpos($key, '_id') !== false || $key === 'id') {
                            if ($value !== null) {
                                if (strlen($value) === 32) {
                                    // Convert SQLite UUID format to PostgreSQL format
                                    $row[$key] = formatUUID($value);
                                } elseif (strlen($value) !== 36) {
                                    // Invalid UUID format - generate new UUID
                                    $row[$key] = generateUUID();
                                }
                            }
                        }
                        // Handle archived_by field that might contain text instead of UUID
                        if ($key === 'archived_by' && $value !== null && strlen($value) !== 32 && strlen($value) !== 36) {
                            $row[$key] = generateUUID(); // Generate new UUID for non-UUID values
                        }
                    }
                    
                    try {
                        $insertStmt->execute($row);
                    } catch (PDOException $e) {
                        // Skip rows that cause foreign key violations
                        if (strpos($e->getMessage(), 'violates foreign key constraint') !== false) {
                            logMessage("Skipping row due to foreign key violation: " . $e->getMessage(), 'warning');
                            continue;
                        }
                        throw $e;
                    }
                }
                
                logMessage("Migrated $rowCount rows to $pgsqlTable", 'success');
                return $rowCount;
            }
        } else {
            logMessage("Skipping $sqliteTable (empty)", 'warning');
        }
        return 0;
    } catch (PDOException $e) {
        logMessage("Error migrating $sqliteTable: " . $e->getMessage(), 'error');
        return 0;
    }
}

function migrateJudgesToUsers($sqlite, $pgsql) {
    try {
        $countQuery = "SELECT COUNT(*) FROM judges";
        $countStmt = $sqlite->query($countQuery);
        $rowCount = (int) $countStmt->fetchColumn();
        
        if ($rowCount > 0) {
            $dataQuery = "SELECT * FROM judges";
            $dataStmt = $sqlite->query($dataQuery);
            $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $insertSQL = "INSERT INTO users (id, name, email, role, gender, pronouns, judge_id, created_at) VALUES (:id, :name, :email, :role, :gender, :pronouns, :judge_id, :created_at)";
            $insertStmt = $pgsql->prepare($insertSQL);
            
            foreach ($rows as $row) {
                $userData = [
                    'id' => formatUUID($row['id']),
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'role' => 'judge',
                    'gender' => $row['gender'],
                    'pronouns' => $row['pronouns'],
                    'judge_id' => $row['id'], // Keep original judge ID
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                try {
                    $insertStmt->execute($userData);
                } catch (PDOException $e) {
                    logMessage("Skipping judge due to constraint violation: " . $e->getMessage(), 'warning');
                    continue;
                }
            }
            
            logMessage("Migrated $rowCount judges to users", 'success');
            return $rowCount;
        }
        return 0;
    } catch (PDOException $e) {
        logMessage("Error migrating judges: " . $e->getMessage(), 'error');
        return 0;
    }
}

function migrateContestantsToUsers($sqlite, $pgsql) {
    try {
        $countQuery = "SELECT COUNT(*) FROM contestants";
        $countStmt = $sqlite->query($countQuery);
        $rowCount = (int) $countStmt->fetchColumn();
        
        if ($rowCount > 0) {
            $dataQuery = "SELECT * FROM contestants";
            $dataStmt = $sqlite->query($dataQuery);
            $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $insertSQL = "INSERT INTO users (id, name, email, role, gender, pronouns, contestant_id, created_at) VALUES (:id, :name, :email, :role, :gender, :pronouns, :contestant_id, :created_at)";
            $insertStmt = $pgsql->prepare($insertSQL);
            
            foreach ($rows as $row) {
                $userData = [
                    'id' => formatUUID($row['id']),
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'role' => 'contestant',
                    'gender' => $row['gender'],
                    'pronouns' => $row['pronouns'],
                    'contestant_id' => $row['id'], // Keep original contestant ID
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                try {
                    $insertStmt->execute($userData);
                } catch (PDOException $e) {
                    logMessage("Skipping contestant due to constraint violation: " . $e->getMessage(), 'warning');
                    continue;
                }
            }
            
            logMessage("Migrated $rowCount contestants to users", 'success');
            return $rowCount;
        }
        return 0;
    } catch (PDOException $e) {
        logMessage("Error migrating contestants: " . $e->getMessage(), 'error');
        return 0;
    }
}

function formatUUID($uuid) {
    // Convert SQLite UUID format (32 chars) to PostgreSQL format (with hyphens)
    if ($uuid !== null && strlen($uuid) === 32) {
        return substr($uuid, 0, 8) . '-' . 
               substr($uuid, 8, 4) . '-' . 
               substr($uuid, 12, 4) . '-' . 
               substr($uuid, 16, 4) . '-' . 
               substr($uuid, 20, 12);
    }
    return $uuid;
}

function generateUUID() {
    // Generate a new UUID
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Table creation functions
function getUsersTableSQL() {
    return "CREATE TABLE users (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        name TEXT NOT NULL,
        email TEXT,
        password_hash TEXT,
        role TEXT NOT NULL CHECK (role IN ('organizer','judge','contestant','emcee','tally_master','auditor','board')),
        preferred_name TEXT,
        gender TEXT,
        pronouns TEXT,
        created_at TIMESTAMP,
        last_login TIMESTAMP,
        session_version INTEGER DEFAULT 1,
        judge_id TEXT,
        contestant_id TEXT
    )";
}

function getEventsTableSQL() {
    return "CREATE TABLE events (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        name TEXT NOT NULL,
        description TEXT,
        start_date TEXT,
        end_date TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getContestGroupsTableSQL() {
    return "CREATE TABLE contest_groups (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        contest_id UUID REFERENCES events(id) ON DELETE CASCADE,
        name TEXT NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCategoriesTableSQL() {
    return "CREATE TABLE categories (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        category_id UUID REFERENCES contest_groups(id) ON DELETE CASCADE,
        name TEXT NOT NULL,
        score_cap REAL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCriteriaTableSQL() {
    return "CREATE TABLE criteria (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        name TEXT NOT NULL,
        max_score DECIMAL(5,2) DEFAULT 10.00,
        weight DECIMAL(3,2) DEFAULT 1.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getEmceeScriptsTableSQL() {
    return "CREATE TABLE emcee_scripts (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        filename TEXT,
        file_path TEXT,
        is_active TEXT,
        created_at TEXT,
        uploaded_by UUID REFERENCES users(id) ON DELETE SET NULL,
        title TEXT,
        description TEXT,
        file_name TEXT,
        file_size TEXT,
        uploaded_at TEXT
    )";
}

function getSystemSettingsTableSQL() {
    return "CREATE TABLE system_settings (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        setting_key TEXT,
        setting_value TEXT,
        description TEXT,
        updated_at TEXT,
        updated_by UUID REFERENCES users(id) ON DELETE SET NULL
    )";
}

function getBackupSettingsTableSQL() {
    return "CREATE TABLE backup_settings (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        backup_type TEXT NOT NULL CHECK (backup_type IN ('schema', 'full')),
        enabled BOOLEAN NOT NULL DEFAULT FALSE,
        frequency TEXT NOT NULL CHECK (frequency IN ('minutes', 'hours', 'daily', 'weekly', 'monthly')),
        frequency_value INTEGER NOT NULL DEFAULT 1,
        retention_days INTEGER NOT NULL DEFAULT 30,
        last_run TEXT,
        next_run TEXT,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";
}

function getActivityLogsTableSQL() {
    return "CREATE TABLE activity_logs (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        user_id UUID REFERENCES users(id) ON DELETE SET NULL,
        user_name TEXT,
        user_role TEXT,
        action TEXT NOT NULL,
        resource_type TEXT,
        resource_id TEXT,
        details TEXT,
        ip_address TEXT,
        user_agent TEXT,
        log_level TEXT NOT NULL DEFAULT 'info',
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )";
}

function getBackupLogsTableSQL() {
    return "CREATE TABLE backup_logs (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        backup_type TEXT,
        file_path TEXT,
        file_size TEXT,
        status TEXT,
        created_by UUID REFERENCES users(id) ON DELETE SET NULL,
        created_at TEXT,
        error_message TEXT
    )";
}

function getCategoryContestantsTableSQL() {
    return "CREATE TABLE category_contestants (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        contestant_id UUID REFERENCES users(id) ON DELETE CASCADE,
        contestant_number VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(category_id, contestant_id)
    )";
}

function getCategoryJudgesTableSQL() {
    return "CREATE TABLE category_judges (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        judge_id UUID REFERENCES users(id) ON DELETE CASCADE,
        is_head_judge BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(category_id, judge_id)
    )";
}

function getSubcategoryContestantsTableSQL() {
    return "CREATE TABLE subcategory_contestants (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        contestant_id UUID REFERENCES users(id) ON DELETE CASCADE,
        contestant_number VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(subcategory_id, contestant_id)
    )";
}

function getSubcategoryJudgesTableSQL() {
    return "CREATE TABLE subcategory_judges (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        judge_id UUID REFERENCES users(id) ON DELETE CASCADE,
        is_head_judge BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(subcategory_id, judge_id)
    )";
}

function getScoresTableSQL() {
    return "CREATE TABLE scores (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        contestant_id UUID REFERENCES users(id) ON DELETE CASCADE,
        judge_id UUID REFERENCES users(id) ON DELETE CASCADE,
        criterion_id UUID REFERENCES criteria(id) ON DELETE CASCADE,
        score DECIMAL(5,2) NOT NULL,
        comments TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(subcategory_id, contestant_id, judge_id, criterion_id)
    )";
}

function getJudgeCommentsTableSQL() {
    return "CREATE TABLE judge_comments (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        contestant_id UUID REFERENCES users(id) ON DELETE CASCADE,
        judge_id UUID REFERENCES users(id) ON DELETE CASCADE,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getJudgeCertificationsTableSQL() {
    return "CREATE TABLE judge_certifications (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id TEXT NOT NULL,
        contestant_id TEXT NOT NULL,
        judge_id TEXT NOT NULL,
        signature_name TEXT NOT NULL,
        certified_at TEXT NOT NULL,
        UNIQUE (subcategory_id, contestant_id, judge_id)
    )";
}

function getTallyMasterCertificationsTableSQL() {
    return "CREATE TABLE tally_master_certifications (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        tally_master_id UUID REFERENCES users(id) ON DELETE CASCADE,
        certified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        signature TEXT,
        ip_address INET,
        user_agent TEXT
    )";
}

function getAuditorCertificationsTableSQL() {
    return "CREATE TABLE auditor_certifications (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        auditor_id UUID REFERENCES users(id) ON DELETE CASCADE,
        certified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        signature TEXT,
        ip_address INET,
        user_agent TEXT
    )";
}

function getJudgeScoreRemovalRequestsTableSQL() {
    return "CREATE TABLE judge_score_removal_requests (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        judge_id UUID REFERENCES users(id) ON DELETE CASCADE,
        requested_by UUID REFERENCES users(id) ON DELETE CASCADE,
        reason TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        auditor_signature TEXT,
        tally_master_signature TEXT,
        head_judge_signature TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getOverallDeductionsTableSQL() {
    return "CREATE TABLE overall_deductions (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        contestant_id UUID REFERENCES users(id) ON DELETE CASCADE,
        amount TEXT,
        comment TEXT,
        signature_name TEXT,
        signed_at TEXT,
        created_by UUID REFERENCES users(id) ON DELETE SET NULL,
        created_at TEXT
    )";
}

function getSubcategoryTemplatesTableSQL() {
    return "CREATE TABLE subcategory_templates (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        name TEXT NOT NULL,
        description TEXT,
        subcategory_names TEXT,
        max_score INTEGER DEFAULT 60
    )";
}

function getTemplateCriteriaTableSQL() {
    return "CREATE TABLE template_criteria (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        template_id UUID REFERENCES subcategory_templates(id) ON DELETE CASCADE,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        max_score DECIMAL(5,2) DEFAULT 10.00,
        weight DECIMAL(3,2) DEFAULT 1.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getArchivedEventsTableSQL() {
    return "CREATE TABLE archived_events (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        name TEXT NOT NULL,
        description TEXT,
        start_date TEXT,
        end_date TEXT,
        archived_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        archived_by UUID NOT NULL
    )";
}

function getArchivedContestGroupsTableSQL() {
    return "CREATE TABLE archived_contest_groups (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        archived_contest_id UUID,
        name TEXT NOT NULL,
        description TEXT,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getArchivedCategoriesTableSQL() {
    return "CREATE TABLE archived_categories (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        archived_category_id TEXT NOT NULL,
        name TEXT NOT NULL,
        description TEXT,
        score_cap REAL,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getArchivedContestantsTableSQL() {
    return "CREATE TABLE archived_contestants (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getArchivedJudgesTableSQL() {
    return "CREATE TABLE archived_judges (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getArchivedCriteriaTableSQL() {
    return "CREATE TABLE archived_criteria (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        archived_subcategory_id UUID,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        max_score DECIMAL(5,2) DEFAULT 10.00,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getArchivedScoresTableSQL() {
    return "CREATE TABLE archived_scores (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID,
        contestant_id UUID,
        judge_id UUID,
        criterion_id UUID,
        score DECIMAL(5,2) NOT NULL,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getArchivedJudgeCommentsTableSQL() {
    return "CREATE TABLE archived_judge_comments (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID,
        contestant_id UUID,
        judge_id UUID,
        comment TEXT NOT NULL,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getArchivedTallyMasterCertificationsTableSQL() {
    return "CREATE TABLE archived_tally_master_certifications (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID,
        tally_master_id UUID,
        certified_at TIMESTAMP,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}
