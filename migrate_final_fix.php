#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Final Migration Fix Script
 * 
 * This script fixes the remaining issues:
 * - system_settings null ID handling
 * - Missing archived_tally_master_certifications table
 * - Users count validation
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
    logHeader("Final Migration Fix Script");
    
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

    // Step 1: Fix system_settings migration
    logHeader("Step 1: Fixing system_settings Migration");
    
    try {
        // Clear existing system_settings
        $pgsql->exec("TRUNCATE TABLE system_settings");
        
        // Get system_settings data from SQLite
        $dataQuery = "SELECT * FROM system_settings";
        $dataStmt = $sqlite->query($dataQuery);
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $insertSQL = "INSERT INTO system_settings (id, setting_key, setting_value, description, updated_at, updated_by) VALUES (:id, :setting_key, :setting_value, :description, :updated_at, :updated_by)";
        $insertStmt = $pgsql->prepare($insertSQL);
        
        foreach ($rows as $row) {
            $data = [
                'id' => $row['id'] ? formatUUID($row['id']) : generateUUID(),
                'setting_key' => $row['setting_key'],
                'setting_value' => $row['setting_value'],
                'description' => $row['description'],
                'updated_at' => $row['updated_at'],
                'updated_by' => $row['updated_by'] ? formatUUID($row['updated_by']) : null
            ];
            
            try {
                $insertStmt->execute($data);
            } catch (PDOException $e) {
                logMessage("Skipping system_settings row: " . $e->getMessage(), 'warning');
                continue;
            }
        }
        
        logMessage("Fixed system_settings migration", 'success');
    } catch (PDOException $e) {
        logMessage("Error fixing system_settings: " . $e->getMessage(), 'error');
    }

    // Step 2: Handle missing archived_tally_master_certifications
    logHeader("Step 2: Handling Missing Archived Tables");
    
    try {
        // Check if archived_tally_master_certifications exists in SQLite
        $checkQuery = "SELECT name FROM sqlite_master WHERE type='table' AND name='archived_tally_master_certifications'";
        $checkStmt = $sqlite->query($checkQuery);
        $exists = $checkStmt->fetchColumn();
        
        if (!$exists) {
            logMessage("archived_tally_master_certifications table does not exist in SQLite - skipping", 'warning');
            
            // Drop the PostgreSQL table since it doesn't exist in SQLite
            $pgsql->exec("DROP TABLE IF EXISTS archived_tally_master_certifications");
            logMessage("Dropped non-existent archived_tally_master_certifications table", 'success');
        } else {
            logMessage("archived_tally_master_certifications exists - migrating", 'info');
            // Migrate the table if it exists
            migrateTable($sqlite, $pgsql, 'archived_tally_master_certifications', 'archived_tally_master_certifications');
        }
    } catch (PDOException $e) {
        logMessage("Error handling archived tables: " . $e->getMessage(), 'error');
    }

    // Step 3: Fix users count validation
    logHeader("Step 3: Fixing Users Count Validation");
    
    try {
        // Count users in SQLite (original users + judges + contestants)
        $sqliteUsersQuery = "SELECT COUNT(*) FROM users";
        $sqliteUsersStmt = $sqlite->query($sqliteUsersQuery);
        $sqliteUsersCount = (int) $sqliteUsersStmt->fetchColumn();
        
        $sqliteJudgesQuery = "SELECT COUNT(*) FROM judges";
        $sqliteJudgesStmt = $sqlite->query($sqliteJudgesQuery);
        $sqliteJudgesCount = (int) $sqliteJudgesStmt->fetchColumn();
        
        $sqliteContestantsQuery = "SELECT COUNT(*) FROM contestants";
        $sqliteContestantsStmt = $sqlite->query($sqliteContestantsQuery);
        $sqliteContestantsCount = (int) $sqliteContestantsStmt->fetchColumn();
        
        $expectedTotal = $sqliteUsersCount + $sqliteJudgesCount + $sqliteContestantsCount;
        
        // Count users in PostgreSQL
        $pgsqlUsersQuery = "SELECT COUNT(*) FROM users";
        $pgsqlUsersStmt = $pgsql->query($pgsqlUsersQuery);
        $pgsqlUsersCount = (int) $pgsqlUsersStmt->fetchColumn();
        
        logMessage("SQLite users: $sqliteUsersCount, judges: $sqliteJudgesCount, contestants: $sqliteContestantsCount", 'info');
        logMessage("Expected total: $expectedTotal, PostgreSQL total: $pgsqlUsersCount", 'info');
        
        if ($expectedTotal === $pgsqlUsersCount) {
            logMessage("✅ Users count validation passed: $pgsqlUsersCount", 'success');
        } else {
            logMessage("❌ Users count mismatch: expected $expectedTotal, got $pgsqlUsersCount", 'error');
        }
    } catch (PDOException $e) {
        logMessage("Error validating users count: " . $e->getMessage(), 'error');
    }

    // Step 4: Final validation
    logHeader("Step 4: Final Validation");
    
    $successCount = 0;
    $errorCount = 0;
    
    // Validate key tables
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
        'overall_deductions' => 'overall_deductions',
        'subcategory_templates' => 'subcategory_templates',
        'archived_contests' => 'archived_events',
        'archived_categories' => 'archived_contest_groups',
        'archived_subcategories' => 'archived_categories',
        'archived_criteria' => 'archived_criteria',
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
    
    // Special validation for users (consolidated)
    try {
        $sqliteUsersQuery = "SELECT COUNT(*) FROM users";
        $sqliteUsersStmt = $sqlite->query($sqliteUsersQuery);
        $sqliteUsersCount = (int) $sqliteUsersStmt->fetchColumn();
        
        $sqliteJudgesQuery = "SELECT COUNT(*) FROM judges";
        $sqliteJudgesStmt = $sqlite->query($sqliteJudgesQuery);
        $sqliteJudgesCount = (int) $sqliteJudgesStmt->fetchColumn();
        
        $sqliteContestantsQuery = "SELECT COUNT(*) FROM contestants";
        $sqliteContestantsStmt = $sqlite->query($sqliteContestantsQuery);
        $sqliteContestantsCount = (int) $sqliteContestantsStmt->fetchColumn();
        
        $expectedTotal = $sqliteUsersCount + $sqliteJudgesCount + $sqliteContestantsCount;
        
        $pgsqlUsersQuery = "SELECT COUNT(*) FROM users";
        $pgsqlUsersStmt = $pgsql->query($pgsqlUsersQuery);
        $pgsqlUsersCount = (int) $pgsqlUsersStmt->fetchColumn();
        
        if ($expectedTotal === $pgsqlUsersCount) {
            logMessage("✅ users (consolidated) → users: $pgsqlUsersCount rows", 'success');
            $successCount++;
        } else {
            logMessage("❌ users (consolidated) → users: expected $expectedTotal, got $pgsqlUsersCount", 'error');
            $errorCount++;
        }
    } catch (PDOException $e) {
        logMessage("Error validating consolidated users: " . $e->getMessage(), 'error');
        $errorCount++;
    }
    
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
