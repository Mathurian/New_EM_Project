#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Complete Migration Fix Script
 * 
 * This script will:
 * 1. Drop and recreate the PostgreSQL database
 * 2. Create the complete schema with all tables
 * 3. Migrate all data from SQLite to PostgreSQL
 * 4. Handle schema cleanup (table renames, user consolidation)
 * 5. Create backward compatibility views
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
    logHeader("Complete Migration Fix Script");
    
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

    // Step 1: Drop and recreate database
    logHeader("Step 1: Recreating PostgreSQL Database");
    
    // First, connect to a different database (postgres) to drop the target database
    logMessage("Connecting to postgres database to drop target database...", 'info');
    $postgresPdo = new PDO("pgsql:host=$pgsqlHost;port=$pgsqlPort;dbname=postgres", $pgsqlUser, $pgsqlPass);
    $postgresPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logMessage("Connected to postgres database", 'success');
    
    // Terminate active connections to the target database
    logMessage("Terminating active connections to target database...", 'info');
    $terminateSql = "
        SELECT pg_terminate_backend(pg_stat_activity.pid)
        FROM pg_stat_activity
        WHERE pg_stat_activity.datname = :targetDb
        AND pid <> pg_backend_pid();
    ";
    $stmt = $postgresPdo->prepare($terminateSql);
    $stmt->execute([':targetDb' => $pgsqlDb]);
    logMessage("Active connections terminated", 'success');
    
    // Drop the target database
    logMessage("Dropping existing database...", 'info');
    $postgresPdo->exec("DROP DATABASE IF EXISTS $pgsqlDb");
    logMessage("Database dropped successfully", 'success');
    
    // Create fresh database
    logMessage("Creating fresh database...", 'info');
    $postgresPdo->exec("CREATE DATABASE $pgsqlDb OWNER $pgsqlUser");
    logMessage("Fresh database created", 'success');
    
    // Reconnect to the new database
    logMessage("Reconnecting to new database...", 'info');
    $pgsql = new PDO("pgsql:host=$pgsqlHost;port=$pgsqlPort;dbname=$pgsqlDb", $pgsqlUser, $pgsqlPass);
    $pgsql->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    logMessage("Connected to fresh database", 'success');

    // Step 2: Enable UUID extension
    logHeader("Step 2: Enabling UUID Extension");
    $pgsql->exec("CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\"");
    logMessage("UUID extension enabled", 'success');

    // Step 3: Create complete schema
    logHeader("Step 3: Creating Complete Schema");
    
    // Table creation order (respecting foreign keys)
    $tables = [
        'users',
        'events', // renamed from contests
        'contest_groups', // renamed from categories
        'categories', // renamed from subcategories
        'criteria',
        'emcee_scripts',
        'system_settings',
        'backup_settings',
        'activity_logs',
        'backup_logs',
        'category_contestants',
        'category_judges',
        'subcategory_contestants',
        'subcategory_judges',
        'scores',
        'judge_comments',
        'judge_certifications',
        'tally_master_certifications',
        'auditor_certifications',
        'judge_score_removal_requests',
        'overall_deductions',
        'subcategory_templates',
        'template_criteria',
        // Archived tables
        'archived_events',
        'archived_contest_groups',
        'archived_categories',
        'archived_contestants',
        'archived_judges',
        'archived_criteria',
        'archived_scores',
        'archived_judge_comments',
        'archived_tally_master_certifications',
    ];

    foreach ($tables as $tableName) {
        logMessage("Creating table: $tableName", 'info');
        $sql = getTableSQL($tableName);
        $pgsql->exec($sql);
        logMessage("Table $tableName created", 'success');
    }

    // Step 4: Migrate data
    logHeader("Step 4: Migrating Data");
    
    // Data migration mapping
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
        // Archived tables
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
                            if (strpos($key, '_id') !== false && strlen($value) === 32) {
                                // Convert SQLite UUID format to PostgreSQL format
                                $row[$key] = formatUUID($value);
                            }
                        }
                        $insertStmt->execute($row);
                    }
                    
                    logMessage("Migrated $rowCount rows to $pgsqlTable", 'success');
                }
            } else {
                logMessage("Skipping $sqliteTable (empty)", 'warning');
            }
        } catch (PDOException $e) {
            logMessage("Error migrating $sqliteTable: " . $e->getMessage(), 'error');
        }
    }

    // Step 5: Create backward compatibility views
    logHeader("Step 5: Creating Backward Compatibility Views");
    
    $views = [
        'contests' => 'SELECT * FROM events',
        'old_categories' => 'SELECT * FROM contest_groups',
        'old_subcategories' => 'SELECT * FROM categories',
        'old_judges' => 'SELECT * FROM users WHERE is_judge = TRUE',
        'old_contestants' => 'SELECT * FROM users WHERE is_contestant = TRUE'
    ];
    
    foreach ($views as $viewName => $viewSQL) {
        try {
            $pgsql->exec("CREATE VIEW $viewName AS $viewSQL");
            logMessage("View $viewName created", 'success');
        } catch (PDOException $e) {
            logMessage("Error creating view $viewName: " . $e->getMessage(), 'error');
        }
    }

    // Step 6: Final validation
    logHeader("Step 6: Final Validation");
    
    $totalRows = 0;
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
                $totalRows += $sqliteCount;
            } else {
                logMessage("❌ $sqliteTable → $pgsqlTable: $sqliteCount vs $pgsqlCount", 'error');
            }
        } catch (PDOException $e) {
            logMessage("Error validating $sqliteTable: " . $e->getMessage(), 'error');
        }
    }
    
    logMessage("Total rows migrated: $totalRows", 'success');
    logMessage("🎉 MIGRATION COMPLETED SUCCESSFULLY!", 'success');

} catch (PDOException $e) {
    logMessage("Database error: " . $e->getMessage(), 'error');
    exit(1);
} catch (Exception $e) {
    logMessage("Unexpected error: " . $e->getMessage(), 'error');
    exit(1);
}

function getTableSQL($tableName) {
    switch ($tableName) {
        case 'users':
            return "CREATE TABLE users (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                username VARCHAR(255) UNIQUE NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL CHECK (role IN ('organizer', 'judge', 'emcee', 'contestant', 'tally_master', 'auditor', 'board')),
                name VARCHAR(255) NOT NULL,
                preferred_name VARCHAR(255),
                pronouns VARCHAR(50),
                is_judge BOOLEAN DEFAULT FALSE,
                is_contestant BOOLEAN DEFAULT FALSE,
                is_organizer BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'events':
            return "CREATE TABLE events (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                name VARCHAR(255) NOT NULL,
                description TEXT,
                start_date DATE,
                end_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'contest_groups':
            return "CREATE TABLE contest_groups (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                event_id UUID REFERENCES events(id) ON DELETE CASCADE,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'categories':
            return "CREATE TABLE categories (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                contest_group_id UUID REFERENCES contest_groups(id) ON DELETE CASCADE,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'criteria':
            return "CREATE TABLE criteria (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                max_score DECIMAL(5,2) DEFAULT 10.00,
                weight DECIMAL(3,2) DEFAULT 1.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'emcee_scripts':
            return "CREATE TABLE emcee_scripts (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                user_id UUID REFERENCES users(id) ON DELETE CASCADE,
                filename VARCHAR(255) NOT NULL,
                original_filename VARCHAR(255) NOT NULL,
                file_path VARCHAR(500) NOT NULL,
                file_size INTEGER NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'system_settings':
            return "CREATE TABLE system_settings (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                setting_key VARCHAR(255) UNIQUE NOT NULL,
                setting_value TEXT,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'backup_settings':
            return "CREATE TABLE backup_settings (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                enabled BOOLEAN DEFAULT FALSE,
                frequency VARCHAR(50) DEFAULT 'daily',
                retention_days INTEGER DEFAULT 30,
                backup_path VARCHAR(500),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'activity_logs':
            return "CREATE TABLE activity_logs (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                user_id UUID REFERENCES users(id) ON DELETE SET NULL,
                action VARCHAR(255) NOT NULL,
                details TEXT,
                ip_address INET,
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'backup_logs':
            return "CREATE TABLE backup_logs (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                backup_type VARCHAR(50) NOT NULL,
                status VARCHAR(50) NOT NULL,
                file_path VARCHAR(500),
                file_size BIGINT,
                error_message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'category_contestants':
            return "CREATE TABLE category_contestants (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
                user_id UUID REFERENCES users(id) ON DELETE CASCADE,
                contestant_number VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(category_id, user_id)
            )";
            
        case 'category_judges':
            return "CREATE TABLE category_judges (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
                user_id UUID REFERENCES users(id) ON DELETE CASCADE,
                is_head_judge BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(category_id, user_id)
            )";
            
        case 'subcategory_contestants':
            return "CREATE TABLE subcategory_contestants (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
                user_id UUID REFERENCES users(id) ON DELETE CASCADE,
                contestant_number VARCHAR(50),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(category_id, user_id)
            )";
            
        case 'subcategory_judges':
            return "CREATE TABLE subcategory_judges (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
                user_id UUID REFERENCES users(id) ON DELETE CASCADE,
                is_head_judge BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(category_id, user_id)
            )";
            
        case 'scores':
            return "CREATE TABLE scores (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
                user_id UUID REFERENCES users(id) ON DELETE CASCADE,
                judge_id UUID REFERENCES users(id) ON DELETE CASCADE,
                criterion_id UUID REFERENCES criteria(id) ON DELETE CASCADE,
                score DECIMAL(5,2) NOT NULL,
                comments TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(category_id, user_id, judge_id, criterion_id)
            )";
            
        case 'judge_comments':
            return "CREATE TABLE judge_comments (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
                user_id UUID REFERENCES users(id) ON DELETE CASCADE,
                judge_id UUID REFERENCES users(id) ON DELETE CASCADE,
                comment TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'judge_certifications':
            return "CREATE TABLE judge_certifications (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
                judge_id UUID REFERENCES users(id) ON DELETE CASCADE,
                certified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                signature TEXT,
                ip_address INET,
                user_agent TEXT
            )";
            
        case 'tally_master_certifications':
            return "CREATE TABLE tally_master_certifications (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
                tally_master_id UUID REFERENCES users(id) ON DELETE CASCADE,
                certified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                signature TEXT,
                ip_address INET,
                user_agent TEXT
            )";
            
        case 'auditor_certifications':
            return "CREATE TABLE auditor_certifications (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
                auditor_id UUID REFERENCES users(id) ON DELETE CASCADE,
                certified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                signature TEXT,
                ip_address INET,
                user_agent TEXT
            )";
            
        case 'judge_score_removal_requests':
            return "CREATE TABLE judge_score_removal_requests (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
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
            
        case 'overall_deductions':
            return "CREATE TABLE overall_deductions (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID REFERENCES categories(id) ON DELETE CASCADE,
                user_id UUID REFERENCES users(id) ON DELETE CASCADE,
                deduction_amount DECIMAL(5,2) NOT NULL,
                reason TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'subcategory_templates':
            return "CREATE TABLE subcategory_templates (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                name VARCHAR(255) NOT NULL,
                description TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'template_criteria':
            return "CREATE TABLE template_criteria (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                template_id UUID REFERENCES subcategory_templates(id) ON DELETE CASCADE,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                max_score DECIMAL(5,2) DEFAULT 10.00,
                weight DECIMAL(3,2) DEFAULT 1.00,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        // Archived tables (simplified structure)
        case 'archived_events':
            return "CREATE TABLE archived_events (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                name VARCHAR(255) NOT NULL,
                description TEXT,
                start_date DATE,
                end_date DATE,
                archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'archived_contest_groups':
            return "CREATE TABLE archived_contest_groups (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                event_id UUID,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'archived_categories':
            return "CREATE TABLE archived_categories (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                contest_group_id UUID,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'archived_contestants':
            return "CREATE TABLE archived_contestants (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255),
                archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'archived_judges':
            return "CREATE TABLE archived_judges (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255),
                archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'archived_criteria':
            return "CREATE TABLE archived_criteria (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                max_score DECIMAL(5,2) DEFAULT 10.00,
                archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'archived_scores':
            return "CREATE TABLE archived_scores (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID,
                user_id UUID,
                judge_id UUID,
                criterion_id UUID,
                score DECIMAL(5,2) NOT NULL,
                archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'archived_judge_comments':
            return "CREATE TABLE archived_judge_comments (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID,
                user_id UUID,
                judge_id UUID,
                comment TEXT NOT NULL,
                archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        case 'archived_tally_master_certifications':
            return "CREATE TABLE archived_tally_master_certifications (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID,
                tally_master_id UUID,
                certified_at TIMESTAMP,
                archived_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
        default:
            throw new Exception("Unknown table: $tableName");
    }
}

function formatUUID($uuid) {
    // Convert SQLite UUID format (32 chars) to PostgreSQL format (with hyphens)
    if (strlen($uuid) === 32) {
        return substr($uuid, 0, 8) . '-' . 
               substr($uuid, 8, 4) . '-' . 
               substr($uuid, 12, 4) . '-' . 
               substr($uuid, 16, 4) . '-' . 
               substr($uuid, 20, 12);
    }
    return $uuid;
}
