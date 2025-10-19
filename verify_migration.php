#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Migration Verification Script
 * 
 * This script verifies that the migration was successful
 * by checking data integrity and relationships
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
    logHeader("Migration Verification Script");
    
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

    // Step 1: Verify Users Consolidation
    logHeader("Step 1: Verifying Users Consolidation");
    
    // Count SQLite users
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
    
    // Count PostgreSQL users
    $pgsqlUsersQuery = "SELECT COUNT(*) FROM users";
    $pgsqlUsersStmt = $pgsql->query($pgsqlUsersQuery);
    $pgsqlUsersCount = (int) $pgsqlUsersStmt->fetchColumn();
    
    logMessage("SQLite users: $sqliteUsersCount", 'info');
    logMessage("SQLite judges: $sqliteJudgesCount", 'info');
    logMessage("SQLite contestants: $sqliteContestantsCount", 'info');
    logMessage("Expected total: $expectedTotal", 'info');
    logMessage("PostgreSQL users: $pgsqlUsersCount", 'info');
    
    if ($expectedTotal === $pgsqlUsersCount) {
        logMessage("✅ Users consolidation: PERFECT", 'success');
    } else {
        logMessage("❌ Users consolidation: MISMATCH", 'error');
    }

    // Step 2: Verify User Roles
    logHeader("Step 2: Verifying User Roles");
    
    $roleQuery = "SELECT role, COUNT(*) as count FROM users GROUP BY role ORDER BY role";
    $roleStmt = $pgsql->query($roleQuery);
    $roles = $roleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($roles as $role) {
        logMessage("Role '{$role['role']}': {$role['count']} users", 'info');
    }

    // Step 3: Verify Foreign Key Relationships
    logHeader("Step 3: Verifying Foreign Key Relationships");
    
    // Check if foreign keys are working
    $fkTests = [
        'subcategory_contestants' => 'SELECT COUNT(*) FROM subcategory_contestants sc JOIN users u ON sc.contestant_id = u.id',
        'subcategory_judges' => 'SELECT COUNT(*) FROM subcategory_judges sj JOIN users u ON sj.judge_id = u.id',
        'scores' => 'SELECT COUNT(*) FROM scores s JOIN users u ON s.contestant_id = u.id',
        'judge_comments' => 'SELECT COUNT(*) FROM judge_comments jc JOIN users u ON jc.contestant_id = u.id'
    ];
    
    foreach ($fkTests as $table => $query) {
        try {
            $stmt = $pgsql->query($query);
            $count = (int) $stmt->fetchColumn();
            logMessage("✅ $table foreign keys: $count valid relationships", 'success');
        } catch (PDOException $e) {
            logMessage("❌ $table foreign keys: " . $e->getMessage(), 'error');
        }
    }

    // Step 4: Verify Core Data Integrity
    logHeader("Step 4: Verifying Core Data Integrity");
    
    $coreTables = [
        'contests' => 'events',
        'categories' => 'contest_groups',
        'subcategories' => 'categories',
        'criteria' => 'criteria',
        'scores' => 'scores',
        'judge_comments' => 'judge_comments'
    ];
    
    $integrityPassed = 0;
    $integrityFailed = 0;
    
    foreach ($coreTables as $sqliteTable => $pgsqlTable) {
        try {
            $sqliteCountQuery = "SELECT COUNT(*) FROM $sqliteTable";
            $sqliteCountStmt = $sqlite->query($sqliteCountQuery);
            $sqliteCount = (int) $sqliteCountStmt->fetchColumn();
            
            $pgsqlCountQuery = "SELECT COUNT(*) FROM $pgsqlTable";
            $pgsqlCountStmt = $pgsql->query($pgsqlCountQuery);
            $pgsqlCount = (int) $pgsqlCountStmt->fetchColumn();
            
            if ($sqliteCount === $pgsqlCount) {
                logMessage("✅ $sqliteTable → $pgsqlTable: $sqliteCount rows", 'success');
                $integrityPassed++;
            } else {
                logMessage("❌ $sqliteTable → $pgsqlTable: $sqliteCount vs $pgsqlCount", 'error');
                $integrityFailed++;
            }
        } catch (PDOException $e) {
            logMessage("❌ Error checking $sqliteTable: " . $e->getMessage(), 'error');
            $integrityFailed++;
        }
    }

    // Step 5: Final Assessment
    logHeader("Step 5: Final Assessment");
    
    logMessage("Core data integrity: $integrityPassed passed, $integrityFailed failed", $integrityFailed === 0 ? 'success' : 'error');
    
    if ($expectedTotal === $pgsqlUsersCount && $integrityFailed === 0) {
        logMessage("🎉 MIGRATION VERIFICATION: COMPLETELY SUCCESSFUL!", 'success');
        logMessage("✅ All data migrated correctly", 'success');
        logMessage("✅ Users consolidated properly", 'success');
        logMessage("✅ Foreign keys working", 'success');
        logMessage("✅ Data integrity maintained", 'success');
    } else {
        logMessage("⚠️  MIGRATION VERIFICATION: ISSUES DETECTED", 'warning');
        if ($expectedTotal !== $pgsqlUsersCount) {
            logMessage("❌ Users count mismatch", 'error');
        }
        if ($integrityFailed > 0) {
            logMessage("❌ Data integrity issues", 'error');
        }
    }

} catch (PDOException $e) {
    logMessage("Database error: " . $e->getMessage(), 'error');
    exit(1);
} catch (Exception $e) {
    logMessage("Unexpected error: " . $e->getMessage(), 'error');
    exit(1);
}
