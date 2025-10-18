<?php
/**
 * Comprehensive Database and Log Permissions Fix Script
 * 
 * This script fixes both database and log file permission issues
 * that cause "attempt to write a readonly database" and log file errors.
 */

require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/helpers.php';

use App\DB;

echo "=== Database and Log Permissions Fix Script ===\n\n";

// Get database path
$dbPath = DB::getDatabasePath();
$dbDir = dirname($dbPath);
$logDir = __DIR__ . '/app/logs';

echo "Database Path: {$dbPath}\n";
echo "Database Directory: {$dbDir}\n";
echo "Log Directory: {$logDir}\n\n";

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

// 1. Fix log directory permissions
echo "1. Fixing Log Directory Permissions:\n";
if (!is_dir($logDir)) {
    echo "   Creating log directory... ";
    if (mkdir($logDir, 0775, true)) {
        echo "SUCCESS\n";
    } else {
        echo "FAILED\n";
    }
}

// Set log directory permissions
runCommand("chmod 775 " . escapeshellarg($logDir), "   Setting log directory permissions");

// 2. Fix database directory permissions
echo "\n2. Fixing Database Directory Permissions:\n";
if (!is_dir($dbDir)) {
    echo "   Creating database directory... ";
    if (mkdir($dbDir, 0775, true)) {
        echo "SUCCESS\n";
    } else {
        echo "FAILED\n";
    }
}

runCommand("chmod 775 " . escapeshellarg($dbDir), "   Setting database directory permissions");

// 3. Fix database file permissions
echo "\n3. Fixing Database File Permissions:\n";
if (file_exists($dbPath)) {
    runCommand("chmod 664 " . escapeshellarg($dbPath), "   Setting database file permissions");
} else {
    echo "   Database file does not exist - will be created on first connection\n";
}

// 4. Set ownership for both directories and files
echo "\n4. Setting File Ownership:\n";
$currentUser = get_current_user();
echo "   Current user: {$currentUser}\n";

// Common web server users
$webUsers = ['www-data', 'apache', 'nginx', 'httpd', 'nobody'];

// Set ownership to current user first
runCommand("chown {$currentUser}:{$currentUser} " . escapeshellarg($logDir), "   Setting log directory ownership to current user");
runCommand("chown {$currentUser}:{$currentUser} " . escapeshellarg($dbDir), "   Setting database directory ownership to current user");
if (file_exists($dbPath)) {
    runCommand("chown {$currentUser}:{$currentUser} " . escapeshellarg($dbPath), "   Setting database file ownership to current user");
}

// Try common web server users
foreach ($webUsers as $webUser) {
    if (function_exists('posix_getpwnam') && posix_getpwnam($webUser)) {
        runCommand("chown {$webUser}:{$webUser} " . escapeshellarg($logDir), "   Setting log directory ownership to {$webUser}");
        runCommand("chown {$webUser}:{$webUser} " . escapeshellarg($dbDir), "   Setting database directory ownership to {$webUser}");
        if (file_exists($dbPath)) {
            runCommand("chown {$webUser}:{$webUser} " . escapeshellarg($dbPath), "   Setting database file ownership to {$webUser}");
        }
        break;
    }
}

// 5. Clean up any lock files
echo "\n5. Cleaning Up Lock Files:\n";
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

// 6. Test database connection and logging
echo "\n6. Testing Database Connection and Logging:\n";
try {
    $pdo = DB::pdo();
    echo "   Database Connection: SUCCESS\n";
    
    // Test write operation
    $testTableName = 'test_fix_' . time();
    $pdo->exec("CREATE TEMPORARY TABLE {$testTableName} (id INTEGER)");
    $pdo->exec("INSERT INTO {$testTableName} (id) VALUES (1)");
    echo "   Database Write Test: SUCCESS\n";
    
    // Test activity_logs table
    $stmt = $pdo->prepare("INSERT INTO activity_logs (id, action, log_level, created_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([uniqid(), 'permission_test', 'info', date('c')]);
    echo "   Activity Logs Write: SUCCESS\n";
    
    // Test file logging
    $logFile = $logDir . '/event-manager-' . date('Y-m-d') . '.log';
    $testLogMessage = "Permission test: " . date('Y-m-d H:i:s') . "\n";
    if (file_put_contents($logFile, $testLogMessage, FILE_APPEND | LOCK_EX)) {
        echo "   File Logging Test: SUCCESS\n";
    } else {
        echo "   File Logging Test: FAILED\n";
    }
    
} catch (Exception $e) {
    echo "   Database Test: FAILED - " . $e->getMessage() . "\n";
}

// 7. Show final permissions
echo "\n7. Final Permissions Check:\n";
echo "   Log Directory: " . substr(sprintf('%o', fileperms($logDir)), -4) . "\n";
echo "   Log Directory Is Writable: " . (is_writable($logDir) ? 'YES' : 'NO') . "\n";

if (file_exists($dbPath)) {
    echo "   Database File: " . substr(sprintf('%o', fileperms($dbPath)), -4) . "\n";
    echo "   Database File Is Writable: " . (is_writable($dbPath) ? 'YES' : 'NO') . "\n";
}
echo "   Database Directory: " . substr(sprintf('%o', fileperms($dbDir)), -4) . "\n";
echo "   Database Directory Is Writable: " . (is_writable($dbDir) ? 'YES' : 'NO') . "\n";

// 8. Check system resources
echo "\n8. System Resources:\n";
echo "   Disk Space: " . shell_exec("df -h " . escapeshellarg($dbDir) . " 2>/dev/null | tail -1") . "\n";
echo "   Inodes: " . shell_exec("df -i " . escapeshellarg($dbDir) . " 2>/dev/null | tail -1") . "\n";

echo "\n=== Fix Complete ===\n";
echo "If issues persist, check:\n";
echo "1. Filesystem mount options (mount | grep " . $dbDir . ")\n";
echo "2. SELinux policies (getenforce, setsebool)\n";
echo "3. AppArmor profiles (aa-status)\n";
echo "4. Web server configuration\n";
echo "5. PHP configuration (open_basedir, etc.)\n";
