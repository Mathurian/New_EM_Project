<?php
/**
 * Database Permissions Fix Script
 * 
 * This script attempts to fix common database permission issues
 * that cause "attempt to write a readonly database" errors.
 */

require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/helpers.php';

use App\DB;

echo "=== Database Permissions Fix Script ===\n\n";

// Get database path
$dbPath = DB::getDatabasePath();
$dbDir = dirname($dbPath);

echo "Database Path: {$dbPath}\n";
echo "Database Directory: {$dbDir}\n\n";

// Function to run shell commands safely
function runCommand($command, $description) {
    echo "{$description}... ";
    $output = shell_exec($command . ' 2>&1');
    if ($output === null) {
        echo "FAILED (command not found)\n";
        return false;
    }
    $output = trim($output);
    if (empty($output)) {
        echo "SUCCESS\n";
        return true;
    } else {
        echo "OUTPUT: {$output}\n";
        return true;
    }
}

// 1. Ensure directory exists and has correct permissions
echo "1. Fixing Directory Permissions:\n";
if (!is_dir($dbDir)) {
    echo "   Creating directory... ";
    if (mkdir($dbDir, 0775, true)) {
        echo "SUCCESS\n";
    } else {
        echo "FAILED\n";
    }
}

// Set directory permissions
runCommand("chmod 775 " . escapeshellarg($dbDir), "   Setting directory permissions");

// 2. Fix database file permissions
echo "\n2. Fixing Database File Permissions:\n";
if (file_exists($dbPath)) {
    runCommand("chmod 664 " . escapeshellarg($dbPath), "   Setting database file permissions");
} else {
    echo "   Database file does not exist - will be created on first connection\n";
}

// 3. Try to determine web server user and set ownership
echo "\n3. Setting File Ownership:\n";

// Common web server users
$webUsers = ['www-data', 'apache', 'nginx', 'httpd', 'nobody'];
$currentUser = get_current_user();

echo "   Current user: {$currentUser}\n";

// Try to set ownership to current user first
runCommand("chown {$currentUser}:{$currentUser} " . escapeshellarg($dbDir), "   Setting directory ownership to current user");
if (file_exists($dbPath)) {
    runCommand("chown {$currentUser}:{$currentUser} " . escapeshellarg($dbPath), "   Setting database file ownership to current user");
}

// Try common web server users
foreach ($webUsers as $webUser) {
    if (function_exists('posix_getpwnam') && posix_getpwnam($webUser)) {
        runCommand("chown {$webUser}:{$webUser} " . escapeshellarg($dbDir), "   Setting directory ownership to {$webUser}");
        if (file_exists($dbPath)) {
            runCommand("chown {$webUser}:{$webUser} " . escapeshellarg($dbPath), "   Setting database file ownership to {$webUser}");
        }
        break;
    }
}

// 4. Clean up any lock files
echo "\n4. Cleaning Up Lock Files:\n";
$lockFiles = [
    $dbPath . '-shm',
    $dbPath . '-wal',
    $dbPath . '-journal'
];

foreach ($lockFiles as $lockFile) {
    if (file_exists($lockFile)) {
        echo "   Removing " . basename($lockFile) . "... ";
        if (unlink($lockFile)) {
            echo "SUCCESS\n";
        } else {
            echo "FAILED\n";
        }
    }
}

// 5. Test database connection after fixes
echo "\n5. Testing Database Connection:\n";
try {
    $pdo = DB::pdo();
    echo "   Connection: SUCCESS\n";
    
    // Test write operation
    $testTableName = 'test_fix_' . time();
    $pdo->exec("CREATE TEMPORARY TABLE {$testTableName} (id INTEGER)");
    $pdo->exec("INSERT INTO {$testTableName} (id) VALUES (1)");
    echo "   Write Test: SUCCESS\n";
    
    // Test activity_logs table
    $stmt = $pdo->prepare("INSERT INTO activity_logs (id, action, log_level, created_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([uniqid(), 'fix_test', 'info', date('c')]);
    echo "   Activity Logs Write: SUCCESS\n";
    
} catch (Exception $e) {
    echo "   Connection: FAILED - " . $e->getMessage() . "\n";
}

// 6. Show final permissions
echo "\n6. Final Permissions Check:\n";
if (file_exists($dbPath)) {
    echo "   Database File: " . substr(sprintf('%o', fileperms($dbPath)), -4) . "\n";
    echo "   Is Writable: " . (is_writable($dbPath) ? 'YES' : 'NO') . "\n";
}
echo "   Database Directory: " . substr(sprintf('%o', fileperms($dbDir)), -4) . "\n";
echo "   Directory Is Writable: " . (is_writable($dbDir) ? 'YES' : 'NO') . "\n";

echo "\n=== Fix Complete ===\n";
echo "If the database is still read-only, check:\n";
echo "1. Filesystem mount options (mount | grep " . $dbDir . ")\n";
echo "2. SELinux policies (getenforce, setsebool)\n";
echo "3. AppArmor profiles (aa-status)\n";
echo "4. Disk space and inodes (df -h, df -i)\n";
