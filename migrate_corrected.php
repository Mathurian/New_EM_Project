#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Corrected Schema and Data Migration Script
 * 
 * This script creates PostgreSQL tables that match the actual SQLite schema
 * and migrates data accordingly.
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
    logHeader("Corrected Schema and Data Migration Script");
    
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

    // Step 2: Drop and recreate tables with correct schema
    logHeader("Step 2: Creating Corrected Tables");
    
    // Drop existing tables first
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
        'archived_scores', 'archived_judge_comments', 'archived_tally_master_certifications'
    ];
    
    foreach ($tablesToDrop as $table) {
        try {
            $pgsql->exec("DROP TABLE IF EXISTS $table CASCADE");
        } catch (PDOException $e) {
            // Ignore errors
        }
    }
    
    // Create tables with correct schemas based on actual SQLite structure
    $tables = [
        'users' => getCorrectedUsersTableSQL(),
        'events' => getCorrectedEventsTableSQL(),
        'contest_groups' => getCorrectedContestGroupsTableSQL(),
        'categories' => getCorrectedCategoriesTableSQL(),
        'criteria' => getCorrectedCriteriaTableSQL(),
        'emcee_scripts' => getCorrectedEmceeScriptsTableSQL(),
        'system_settings' => getCorrectedSystemSettingsTableSQL(),
        'backup_settings' => getCorrectedBackupSettingsTableSQL(),
        'activity_logs' => getCorrectedActivityLogsTableSQL(),
        'backup_logs' => getCorrectedBackupLogsTableSQL(),
        'category_contestants' => getCorrectedCategoryContestantsTableSQL(),
        'category_judges' => getCorrectedCategoryJudgesTableSQL(),
        'subcategory_contestants' => getCorrectedSubcategoryContestantsTableSQL(),
        'subcategory_judges' => getCorrectedSubcategoryJudgesTableSQL(),
        'scores' => getCorrectedScoresTableSQL(),
        'judge_comments' => getCorrectedJudgeCommentsTableSQL(),
        'judge_certifications' => getCorrectedJudgeCertificationsTableSQL(),
        'tally_master_certifications' => getCorrectedTallyMasterCertificationsTableSQL(),
        'auditor_certifications' => getCorrectedAuditorCertificationsTableSQL(),
        'judge_score_removal_requests' => getCorrectedJudgeScoreRemovalRequestsTableSQL(),
        'overall_deductions' => getCorrectedOverallDeductionsTableSQL(),
        'subcategory_templates' => getCorrectedSubcategoryTemplatesTableSQL(),
        'template_criteria' => getCorrectedTemplateCriteriaTableSQL(),
        'archived_events' => getCorrectedArchivedEventsTableSQL(),
        'archived_contest_groups' => getCorrectedArchivedContestGroupsTableSQL(),
        'archived_categories' => getCorrectedArchivedCategoriesTableSQL(),
        'archived_contestants' => getCorrectedArchivedContestantsTableSQL(),
        'archived_judges' => getCorrectedArchivedJudgesTableSQL(),
        'archived_criteria' => getCorrectedArchivedCriteriaTableSQL(),
        'archived_scores' => getCorrectedArchivedScoresTableSQL(),
        'archived_judge_comments' => getCorrectedArchivedJudgeCommentsTableSQL(),
        'archived_tally_master_certifications' => getCorrectedArchivedTallyMasterCertificationsTableSQL(),
    ];

    foreach ($tables as $tableName => $sql) {
        try {
            $pgsql->exec($sql);
            logMessage("Table $tableName created", 'success');
        } catch (PDOException $e) {
            logMessage("Error creating table $tableName: " . $e->getMessage(), 'error');
        }
    }

    // Step 3: Migrate data
    logHeader("Step 3: Migrating Data");
    
    $dataMapping = [
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
        'category_contestants' => 'category_contestants',
        'category_judges' => 'category_judges',
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

    $totalMigrated = 0;
    
    foreach ($dataMapping as $sqliteTable => $pgsqlTable) {
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
                    
                    // Insert each row
                    foreach ($rows as $row) {
                        // Handle UUID format conversion
                        foreach ($row as $key => $value) {
                            if (strpos($key, '_id') !== false && $value !== null && strlen($value) === 32) {
                                // Convert SQLite UUID format to PostgreSQL format
                                $row[$key] = formatUUID($value);
                            }
                        }
                        $insertStmt->execute($row);
                    }
                    
                    logMessage("Migrated $rowCount rows to $pgsqlTable", 'success');
                    $totalMigrated += $rowCount;
                }
            } else {
                logMessage("Skipping $sqliteTable (empty)", 'warning');
            }
        } catch (PDOException $e) {
            logMessage("Error migrating $sqliteTable: " . $e->getMessage(), 'error');
        }
    }

    // Step 4: Create backward compatibility views
    logHeader("Step 4: Creating Backward Compatibility Views");
    
    $views = [
        'contests' => 'SELECT * FROM events',
        'old_categories' => 'SELECT * FROM contest_groups',
        'old_subcategories' => 'SELECT * FROM categories',
        'old_judges' => 'SELECT * FROM users WHERE is_judge = TRUE',
        'old_contestants' => 'SELECT * FROM users WHERE is_contestant = TRUE'
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
    
    foreach ($dataMapping as $sqliteTable => $pgsqlTable) {
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

// Corrected table creation functions based on actual SQLite schema
function getCorrectedUsersTableSQL() {
    return "CREATE TABLE users (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        username VARCHAR(255) UNIQUE NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(50) NOT NULL CHECK (role IN ('organizer', 'judge', 'emcee', 'contestant', 'tally_master', 'auditor', 'board')),
        name VARCHAR(255) NOT NULL,
        preferred_name VARCHAR(255),
        gender VARCHAR(50),
        pronouns VARCHAR(50),
        is_judge BOOLEAN DEFAULT FALSE,
        is_contestant BOOLEAN DEFAULT FALSE,
        is_organizer BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedEventsTableSQL() {
    return "CREATE TABLE events (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        name VARCHAR(255) NOT NULL,
        description TEXT,
        start_date DATE,
        end_date DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedContestGroupsTableSQL() {
    return "CREATE TABLE contest_groups (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        contest_id UUID REFERENCES events(id) ON DELETE CASCADE,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedCategoriesTableSQL() {
    return "CREATE TABLE categories (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        category_id UUID REFERENCES contest_groups(id) ON DELETE CASCADE,
        name VARCHAR(255) NOT NULL,
        score_cap DECIMAL(5,2),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedCriteriaTableSQL() {
    return "CREATE TABLE criteria (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        name VARCHAR(255) NOT NULL,
        max_score DECIMAL(5,2) DEFAULT 10.00,
        weight DECIMAL(3,2) DEFAULT 1.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedEmceeScriptsTableSQL() {
    return "CREATE TABLE emcee_scripts (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        filename VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedSystemSettingsTableSQL() {
    return "CREATE TABLE system_settings (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        setting_key VARCHAR(255) UNIQUE NOT NULL,
        setting_value TEXT,
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_by UUID REFERENCES users(id) ON DELETE SET NULL
    )";
}

function getCorrectedBackupSettingsTableSQL() {
    return "CREATE TABLE backup_settings (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        backup_type VARCHAR(50) NOT NULL,
        enabled BOOLEAN DEFAULT FALSE,
        frequency VARCHAR(50) DEFAULT 'daily',
        retention_days INTEGER DEFAULT 30,
        backup_path VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedActivityLogsTableSQL() {
    return "CREATE TABLE activity_logs (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        user_id UUID REFERENCES users(id) ON DELETE SET NULL,
        user_name VARCHAR(255),
        user_role VARCHAR(50),
        action VARCHAR(255) NOT NULL,
        details TEXT,
        ip_address INET,
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedBackupLogsTableSQL() {
    return "CREATE TABLE backup_logs (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        backup_type VARCHAR(50) NOT NULL,
        file_path VARCHAR(500),
        file_size BIGINT,
        status VARCHAR(50) NOT NULL,
        created_by UUID REFERENCES users(id) ON DELETE SET NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedCategoryContestantsTableSQL() {
    return "CREATE TABLE category_contestants (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        contestant_id UUID REFERENCES users(id) ON DELETE CASCADE,
        contestant_number VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(category_id, contestant_id)
    )";
}

function getCorrectedCategoryJudgesTableSQL() {
    return "CREATE TABLE category_judges (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        judge_id UUID REFERENCES users(id) ON DELETE CASCADE,
        is_head_judge BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(category_id, judge_id)
    )";
}

function getCorrectedSubcategoryContestantsTableSQL() {
    return "CREATE TABLE subcategory_contestants (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        contestant_id UUID REFERENCES users(id) ON DELETE CASCADE,
        contestant_number VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(subcategory_id, contestant_id)
    )";
}

function getCorrectedSubcategoryJudgesTableSQL() {
    return "CREATE TABLE subcategory_judges (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        judge_id UUID REFERENCES users(id) ON DELETE CASCADE,
        is_head_judge BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(subcategory_id, judge_id)
    )";
}

function getCorrectedScoresTableSQL() {
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

function getCorrectedJudgeCommentsTableSQL() {
    return "CREATE TABLE judge_comments (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        contestant_id UUID REFERENCES users(id) ON DELETE CASCADE,
        judge_id UUID REFERENCES users(id) ON DELETE CASCADE,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedJudgeCertificationsTableSQL() {
    return "CREATE TABLE judge_certifications (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        contestant_id UUID REFERENCES users(id) ON DELETE CASCADE,
        judge_id UUID REFERENCES users(id) ON DELETE CASCADE,
        certified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        signature TEXT,
        ip_address INET,
        user_agent TEXT
    )";
}

function getCorrectedTallyMasterCertificationsTableSQL() {
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

function getCorrectedAuditorCertificationsTableSQL() {
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

function getCorrectedJudgeScoreRemovalRequestsTableSQL() {
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

function getCorrectedOverallDeductionsTableSQL() {
    return "CREATE TABLE overall_deductions (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID REFERENCES categories(id) ON DELETE CASCADE,
        contestant_id UUID REFERENCES users(id) ON DELETE CASCADE,
        deduction_amount DECIMAL(5,2) NOT NULL,
        reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedSubcategoryTemplatesTableSQL() {
    return "CREATE TABLE subcategory_templates (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        name VARCHAR(255) NOT NULL,
        description TEXT,
        subcategory_names TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedTemplateCriteriaTableSQL() {
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

function getCorrectedArchivedEventsTableSQL() {
    return "CREATE TABLE archived_events (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        name VARCHAR(255) NOT NULL,
        description TEXT,
        start_date DATE,
        end_date DATE,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        archived_by UUID REFERENCES users(id) ON DELETE SET NULL
    )";
}

function getCorrectedArchivedContestGroupsTableSQL() {
    return "CREATE TABLE archived_contest_groups (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        archived_contest_id UUID,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedArchivedCategoriesTableSQL() {
    return "CREATE TABLE archived_categories (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        archived_category_id UUID,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedArchivedContestantsTableSQL() {
    return "CREATE TABLE archived_contestants (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedArchivedJudgesTableSQL() {
    return "CREATE TABLE archived_judges (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255),
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedArchivedCriteriaTableSQL() {
    return "CREATE TABLE archived_criteria (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        archived_subcategory_id UUID,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        max_score DECIMAL(5,2) DEFAULT 10.00,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedArchivedScoresTableSQL() {
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

function getCorrectedArchivedJudgeCommentsTableSQL() {
    return "CREATE TABLE archived_judge_comments (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID,
        contestant_id UUID,
        judge_id UUID,
        comment TEXT NOT NULL,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}

function getCorrectedArchivedTallyMasterCertificationsTableSQL() {
    return "CREATE TABLE archived_tally_master_certifications (
        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
        subcategory_id UUID,
        tally_master_id UUID,
        certified_at TIMESTAMP,
        archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
}
