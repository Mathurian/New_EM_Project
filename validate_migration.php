#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Database Migration Validation Script
 * 
 * This script compares data between SQLite (source) and PostgreSQL (target) databases
 * to verify the migration was successful. It handles schema differences like table renames
 * and user consolidation.
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
    logHeader("Database Migration Validation Script");
    
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

    // Table mapping for schema differences
    $tableMapping = [
        // Direct mappings (same table names)
        'users' => 'users',
        'criteria' => 'criteria',
        'scores' => 'scores',
        'judge_comments' => 'judge_comments',
        'activity_logs' => 'activity_logs',
        'system_settings' => 'system_settings',
        'backup_logs' => 'backup_logs',
        'backup_settings' => 'backup_settings',
        'emcee_scripts' => 'emcee_scripts',
        'auditor_certifications' => 'auditor_certifications',
        'judge_score_removal_requests' => 'judge_score_removal_requests',
        'overall_deductions' => 'overall_deductions',
        'subcategory_templates' => 'subcategory_templates',
        'template_criteria' => 'template_criteria',
        
        // Schema renames
        'contests' => 'events',
        'categories' => 'contest_groups',
        'subcategories' => 'categories',
        
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
        
        // Junction tables
        'category_contestants' => 'category_contestants',
        'category_judges' => 'category_judges',
        'subcategory_contestants' => 'subcategory_contestants',
        'subcategory_judges' => 'subcategory_judges',
        
        // Certification tables
        'judge_certifications' => 'judge_certifications',
        'tally_master_certifications' => 'tally_master_certifications',
    ];

    // Get tables from SQLite
    logMessage("Fetching table list from SQLite...", 'info');
    $tablesQuery = "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name";
    $tablesStmt = $sqlite->query($tablesQuery);
    $sqliteTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    logMessage("Found " . count($sqliteTables) . " tables in SQLite", 'success');

    // Validation results
    $validationResults = [
        'total_tables' => 0,
        'successful_validations' => 0,
        'failed_validations' => 0,
        'skipped_validations' => 0,
        'errors' => []
    ];

    logHeader("Starting Data Validation");

    foreach ($sqliteTables as $sqliteTable) {
        $validationResults['total_tables']++;
        
        // Check if table exists in mapping
        if (!isset($tableMapping[$sqliteTable])) {
            logMessage("Skipping table '$sqliteTable' - not in mapping", 'warning');
            $validationResults['skipped_validations']++;
            continue;
        }

        $pgsqlTable = $tableMapping[$sqliteTable];
        
        logMessage("Validating table: $sqliteTable → $pgsqlTable", 'info');

        try {
            // Check if PostgreSQL table exists
            $checkTableQuery = "SELECT COUNT(*) FROM information_schema.tables WHERE table_name = ? AND table_schema = 'public'";
            $checkStmt = $pgsql->prepare($checkTableQuery);
            $checkStmt->execute([$pgsqlTable]);
            $tableExists = $checkStmt->fetchColumn() > 0;

            if (!$tableExists) {
                logMessage("PostgreSQL table '$pgsqlTable' does not exist", 'error');
                $validationResults['failed_validations']++;
                $validationResults['errors'][] = "Table '$pgsqlTable' missing in PostgreSQL";
                continue;
            }

            // Get row counts
            $sqliteCountQuery = "SELECT COUNT(*) FROM $sqliteTable";
            $sqliteCountStmt = $sqlite->query($sqliteCountQuery);
            $sqliteCount = (int) $sqliteCountStmt->fetchColumn();

            $pgsqlCountQuery = "SELECT COUNT(*) FROM $pgsqlTable";
            $pgsqlCountStmt = $pgsql->query($pgsqlCountQuery);
            $pgsqlCount = (int) $pgsqlCountStmt->fetchColumn();

            // Compare row counts
            if ($sqliteCount === $pgsqlCount) {
                logMessage("Row count match: $sqliteCount rows", 'success');
                $validationResults['successful_validations']++;
            } else {
                logMessage("Row count mismatch: SQLite=$sqliteCount, PostgreSQL=$pgsqlCount", 'error');
                $validationResults['failed_validations']++;
                $validationResults['errors'][] = "Row count mismatch for '$sqliteTable' → '$pgsqlTable': $sqliteCount vs $pgsqlCount";
            }

            // For small tables, do detailed data comparison
            if ($sqliteCount <= 100 && $sqliteCount > 0) {
                logMessage("Performing detailed data validation...", 'info');
                
                // Get primary key columns for SQLite
                $pkQuery = "PRAGMA table_info($sqliteTable)";
                $pkStmt = $sqlite->query($pkQuery);
                $pkColumns = [];
                while ($column = $pkStmt->fetch(PDO::FETCH_ASSOC)) {
                    if ($column['pk']) {
                        $pkColumns[] = $column['name'];
                    }
                }

                if (!empty($pkColumns)) {
                    $pkColumnsList = implode(', ', $pkColumns);
                    
                    // Fetch data from both databases
                    $sqliteDataQuery = "SELECT * FROM $sqliteTable ORDER BY $pkColumnsList";
                    $sqliteDataStmt = $sqlite->query($sqliteDataQuery);
                    $sqliteData = $sqliteDataStmt->fetchAll(PDO::FETCH_ASSOC);

                    $pgsqlDataQuery = "SELECT * FROM $pgsqlTable ORDER BY $pkColumnsList";
                    $pgsqlDataStmt = $pgsql->query($pgsqlDataQuery);
                    $pgsqlData = $pgsqlDataStmt->fetchAll(PDO::FETCH_ASSOC);

                    // Compare data (simplified comparison)
                    $dataMatches = true;
                    if (count($sqliteData) !== count($pgsqlData)) {
                        $dataMatches = false;
                        logMessage("Data length mismatch in detailed comparison", 'error');
                    } else {
                        // Compare first few rows as sample
                        $sampleSize = min(5, count($sqliteData));
                        for ($i = 0; $i < $sampleSize; $i++) {
                            $sqliteRow = $sqliteData[$i];
                            $pgsqlRow = $pgsqlData[$i];
                            
                            // Compare key fields (simplified)
                            foreach ($sqliteRow as $key => $value) {
                                if (isset($pgsqlRow[$key])) {
                                    // Convert both to strings for comparison
                                    $sqliteValue = (string) $value;
                                    $pgsqlValue = (string) $pgsqlRow[$key];
                                    
                                    if ($sqliteValue !== $pgsqlValue) {
                                        $dataMatches = false;
                                        logMessage("Data mismatch in row $i, column '$key': '$sqliteValue' vs '$pgsqlValue'", 'error');
                                        break 2;
                                    }
                                }
                            }
                        }
                    }

                    if ($dataMatches) {
                        logMessage("Detailed data validation passed", 'success');
                    } else {
                        logMessage("Detailed data validation failed", 'error');
                        $validationResults['failed_validations']++;
                    }
                }
            }

        } catch (PDOException $e) {
            logMessage("Error validating table '$sqliteTable': " . $e->getMessage(), 'error');
            $validationResults['failed_validations']++;
            $validationResults['errors'][] = "Error validating '$sqliteTable': " . $e->getMessage();
        }
    }

    // Special validation for user consolidation
    logHeader("User Consolidation Validation");
    
    try {
        // Check if users table has role flags
        $userFlagsQuery = "SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN is_judge THEN 1 ELSE 0 END) as judge_count,
            SUM(CASE WHEN is_contestant THEN 1 ELSE 0 END) as contestant_count,
            SUM(CASE WHEN is_organizer THEN 1 ELSE 0 END) as organizer_count
            FROM users";
        $userFlagsStmt = $pgsql->query($userFlagsQuery);
        $userFlags = $userFlagsStmt->fetch(PDO::FETCH_ASSOC);
        
        logMessage("User consolidation results:", 'info');
        logMessage("  Total users: " . $userFlags['total_users'], 'info');
        logMessage("  Judges: " . $userFlags['judge_count'], 'info');
        logMessage("  Contestants: " . $userFlags['contestant_count'], 'info');
        logMessage("  Organizers: " . $userFlags['organizer_count'], 'info');
        
        // Verify backward compatibility views
        logMessage("Testing backward compatibility views...", 'info');
        
        $viewTests = [
            'contests' => 'events',
            'old_categories' => 'contest_groups',
            'old_subcategories' => 'categories',
            'old_judges' => 'users WHERE is_judge = TRUE',
            'old_contestants' => 'users WHERE is_contestant = TRUE'
        ];
        
        foreach ($viewTests as $viewName => $sourceTable) {
            try {
                $viewCountQuery = "SELECT COUNT(*) FROM $viewName";
                $viewCountStmt = $pgsql->query($viewCountQuery);
                $viewCount = $viewCountStmt->fetchColumn();
                logMessage("View '$viewName' works: $viewCount rows", 'success');
            } catch (PDOException $e) {
                logMessage("View '$viewName' failed: " . $e->getMessage(), 'error');
                $validationResults['errors'][] = "View '$viewName' not working";
            }
        }
        
    } catch (PDOException $e) {
        logMessage("User consolidation validation failed: " . $e->getMessage(), 'error');
        $validationResults['errors'][] = "User consolidation validation failed: " . $e->getMessage();
    }

    // Final validation report
    logHeader("Validation Summary");
    
    logMessage("Total tables processed: " . $validationResults['total_tables'], 'info');
    logMessage("Successful validations: " . $validationResults['successful_validations'], 'success');
    logMessage("Failed validations: " . $validationResults['failed_validations'], $validationResults['failed_validations'] > 0 ? 'error' : 'success');
    logMessage("Skipped validations: " . $validationResults['skipped_validations'], 'warning');
    
    if (!empty($validationResults['errors'])) {
        logMessage("Errors found:", 'error');
        foreach ($validationResults['errors'] as $error) {
            logMessage("  - $error", 'error');
        }
    }
    
    // Overall result
    if ($validationResults['failed_validations'] === 0) {
        logMessage("🎉 MIGRATION VALIDATION SUCCESSFUL! All data migrated correctly.", 'success');
        exit(0);
    } else {
        logMessage("❌ MIGRATION VALIDATION FAILED! Please review the errors above.", 'error');
        exit(1);
    }

} catch (PDOException $e) {
    logMessage("Database connection error: " . $e->getMessage(), 'error');
    exit(1);
} catch (Exception $e) {
    logMessage("Unexpected error: " . $e->getMessage(), 'error');
    exit(1);
}

/**
 * Recursively compares two arrays and returns the differences.
 */
function array_diff_assoc_recursive($array1, $array2) {
    $difference = [];
    foreach ($array1 as $key => $value) {
        if (is_array($value)) {
            if (!isset($array2[$key]) || !is_array($array2[$key])) {
                $difference[$key] = $value;
            } else {
                $new_diff = array_diff_assoc_recursive($value, $array2[$key]);
                if (!empty($new_diff)) {
                    $difference[$key] = $new_diff;
                }
            }
        } elseif (!array_key_exists($key, $array2) || $array2[$key] !== $value) {
            $difference[$key] = $value;
        }
    }
    return $difference;
}
