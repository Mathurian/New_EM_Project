<?php
/**
 * Database Permissions Diagnostic Script
 * 
 * This script helps diagnose "attempt to write a readonly database" errors
 * by checking file permissions, directory permissions, and database accessibility.
 */

require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/helpers.php';

use App\DB;

echo "=== Database Permissions Diagnostic ===\n\n";

// 1. Check database path and file existence
echo "1. Database Path Analysis:\n";
$dbPath = DB::getDatabasePath();
echo "   Database Path: {$dbPath}\n";
echo "   File Exists: " . (file_exists($dbPath) ? 'YES' : 'NO') . "\n";

if (file_exists($dbPath)) {
    echo "   File Size: " . number_format(filesize($dbPath)) . " bytes\n";
    echo "   File Permissions: " . substr(sprintf('%o', fileperms($dbPath)), -4) . "\n";
    echo "   Is Readable: " . (is_readable($dbPath) ? 'YES' : 'NO') . "\n";
    echo "   Is Writable: " . (is_writable($dbPath) ? 'YES' : 'NO') . "\n";
}

// 2. Check directory permissions
echo "\n2. Directory Analysis:\n";
$dbDir = dirname($dbPath);
echo "   Directory: {$dbDir}\n";
echo "   Directory Exists: " . (is_dir($dbDir) ? 'YES' : 'NO') . "\n";

if (is_dir($dbDir)) {
    echo "   Directory Permissions: " . substr(sprintf('%o', fileperms($dbDir)), -4) . "\n";
    echo "   Is Readable: " . (is_readable($dbDir) ? 'YES' : 'NO') . "\n";
    echo "   Is Writable: " . (is_writable($dbDir) ? 'YES' : 'NO') . "\n";
}

// 3. Check filesystem mount status
echo "\n3. Filesystem Analysis:\n";
$mountInfo = shell_exec("mount | grep " . escapeshellarg($dbDir) . " 2>/dev/null");
if ($mountInfo) {
    echo "   Mount Info: " . trim($mountInfo) . "\n";
} else {
    echo "   Mount Info: Could not determine mount status\n";
}

// 4. Check web server user
echo "\n4. Web Server User Analysis:\n";
echo "   Current User: " . get_current_user() . "\n";
echo "   Process User: " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'Unknown') . "\n";
echo "   Process Group: " . (function_exists('posix_getgrgid') ? posix_getgrgid(posix_getegid())['name'] : 'Unknown') . "\n";

// 5. Test database connection and basic operations
echo "\n5. Database Connection Test:\n";
try {
    $pdo = DB::pdo();
    echo "   Connection: SUCCESS\n";
    
    // Test read operation
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sqlite_master WHERE type='table'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Read Test: SUCCESS (found {$result['count']} tables)\n";
    
    // Test write operation (create a temporary table)
    $testTableName = 'test_permissions_' . time();
    $pdo->exec("CREATE TEMPORARY TABLE {$testTableName} (id INTEGER)");
    echo "   Write Test: SUCCESS\n";
    
    // Test insert operation
    $pdo->exec("INSERT INTO {$testTableName} (id) VALUES (1)");
    echo "   Insert Test: SUCCESS\n";
    
    // Test activity_logs table specifically
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (id, action, log_level, created_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([uniqid(), 'permission_test', 'info', date('c')]);
        echo "   Activity Logs Write: SUCCESS\n";
    } catch (Exception $e) {
        echo "   Activity Logs Write: FAILED - " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "   Connection: FAILED - " . $e->getMessage() . "\n";
}

// 6. Check for SQLite lock files
echo "\n6. SQLite Lock Files:\n";
$lockFiles = [
    $dbPath . '-shm',
    $dbPath . '-wal',
    $dbPath . '-journal'
];

foreach ($lockFiles as $lockFile) {
    if (file_exists($lockFile)) {
        echo "   " . basename($lockFile) . ": EXISTS (size: " . filesize($lockFile) . " bytes)\n";
    } else {
        echo "   " . basename($lockFile) . ": NOT FOUND\n";
    }
}

// 7. Check system resources
echo "\n7. System Resources:\n";
echo "   Disk Space: " . shell_exec("df -h " . escapeshellarg($dbDir) . " 2>/dev/null | tail -1") . "\n";
echo "   Inodes: " . shell_exec("df -i " . escapeshellarg($dbDir) . " 2>/dev/null | tail -1") . "\n";

// 8. Check for SELinux/AppArmor (if available)
echo "\n8. Security Modules:\n";
if (function_exists('shell_exec')) {
    $selinux = shell_exec("getenforce 2>/dev/null");
    if ($selinux) {
        echo "   SELinux: " . trim($selinux) . "\n";
    }
    
    $apparmor = shell_exec("aa-status 2>/dev/null");
    if ($apparmor) {
        echo "   AppArmor: ACTIVE\n";
    } else {
        echo "   AppArmor: NOT ACTIVE\n";
    }
}

echo "\n=== Diagnostic Complete ===\n";
echo "If you see 'Write Test: FAILED' or 'Activity Logs Write: FAILED',\n";
echo "this indicates the database is indeed read-only.\n";
echo "\nCommon fixes:\n";
echo "1. chmod 664 " . $dbPath . "\n";
echo "2. chmod 775 " . $dbDir . "\n";
echo "3. chown www-data:www-data " . $dbPath . "\n";
echo "4. chown www-data:www-data " . $dbDir . "\n";
echo "5. Check filesystem mount options\n";
echo "6. Check SELinux/AppArmor policies\n";
