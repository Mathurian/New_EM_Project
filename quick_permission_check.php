<?php
/**
 * Quick Permission Diagnostic Script
 * 
 * This script quickly identifies permission issues without making changes.
 */

require_once __DIR__ . '/app/lib/DB.php';

use App\DB;

echo "=== Quick Permission Diagnostic ===\n\n";

// Get paths
$dbPath = DB::getDatabasePath();
$dbDir = dirname($dbPath);
$logDir = __DIR__ . '/app/logs';

echo "Paths:\n";
echo "  Database: {$dbPath}\n";
echo "  DB Directory: {$dbDir}\n";
echo "  Log Directory: {$logDir}\n\n";

// Check permissions
echo "Permissions:\n";
echo "  DB Directory exists: " . (is_dir($dbDir) ? 'YES' : 'NO') . "\n";
echo "  DB Directory writable: " . (is_writable($dbDir) ? 'YES' : 'NO') . "\n";
echo "  DB File exists: " . (file_exists($dbPath) ? 'YES' : 'NO') . "\n";
if (file_exists($dbPath)) {
    echo "  DB File writable: " . (is_writable($dbPath) ? 'YES' : 'NO') . "\n";
}
echo "  Log Directory exists: " . (is_dir($logDir) ? 'YES' : 'NO') . "\n";
echo "  Log Directory writable: " . (is_writable($logDir) ? 'YES' : 'NO') . "\n\n";

// Check ownership
echo "Ownership:\n";
echo "  Current user: " . get_current_user() . "\n";
echo "  Process user: " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'Unknown') . "\n";
if (is_dir($dbDir)) {
    echo "  DB Directory owner: " . (function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($dbDir))['name'] : 'Unknown') . "\n";
}
if (file_exists($dbPath)) {
    echo "  DB File owner: " . (function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($dbPath))['name'] : 'Unknown') . "\n";
}
if (is_dir($logDir)) {
    echo "  Log Directory owner: " . (function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($logDir))['name'] : 'Unknown') . "\n";
}
echo "\n";

// Test database connection
echo "Database Test:\n";
try {
    $pdo = DB::pdo();
    echo "  Connection: SUCCESS\n";
    
    // Test write
    $pdo->exec("CREATE TEMPORARY TABLE test_perms (id INTEGER)");
    echo "  Write Test: SUCCESS\n";
    
} catch (Exception $e) {
    echo "  Connection: FAILED - " . $e->getMessage() . "\n";
}

// Test file logging
echo "\nFile Logging Test:\n";
$testLogFile = $logDir . '/test-permissions.log';
try {
    if (file_put_contents($testLogFile, "Test: " . date('Y-m-d H:i:s') . "\n")) {
        echo "  File Write: SUCCESS\n";
        unlink($testLogFile); // Clean up
    } else {
        echo "  File Write: FAILED\n";
    }
} catch (Exception $e) {
    echo "  File Write: FAILED - " . $e->getMessage() . "\n";
}

echo "\n=== Diagnostic Complete ===\n";
