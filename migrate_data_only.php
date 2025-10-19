#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Data-Only Migration Script
 * 
 * This script migrates only the data from SQLite to PostgreSQL,
 * assuming the schema already exists in PostgreSQL.
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
    logHeader("Data-Only Migration Script");
    
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

    // Clear existing data
    logHeader("Clearing Existing PostgreSQL Data");
    
    $tablesToClear = [
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
    
    foreach ($tablesToClear as $table) {
        try {
            $pgsql->exec("TRUNCATE TABLE $table CASCADE");
            logMessage("Cleared table: $table", 'success');
        } catch (PDOException $e) {
            logMessage("Could not clear $table: " . $e->getMessage(), 'warning');
        }
    }

    // Data migration mapping
    logHeader("Migrating Data");
    
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
                            if (strpos($key, '_id') !== false && strlen($value) === 32) {
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

    // Final validation
    logHeader("Final Validation");
    
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
        logMessage("🎉 DATA MIGRATION COMPLETED SUCCESSFULLY!", 'success');
    } else {
        logMessage("❌ DATA MIGRATION COMPLETED WITH ERRORS!", 'error');
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
    if (strlen($uuid) === 32) {
        return substr($uuid, 0, 8) . '-' . 
               substr($uuid, 8, 4) . '-' . 
               substr($uuid, 12, 4) . '-' . 
               substr($uuid, 16, 4) . '-' . 
               substr($uuid, 20, 12);
    }
    return $uuid;
}
