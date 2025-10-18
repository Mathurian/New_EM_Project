<?php
/**
 * Log Directory and File Permissions Diagnostic Script
 * 
 * This script diagnoses issues with the log directory and files that could
 * be causing the 500 error on the log file management page.
 */

echo "=== Log Directory and File Permissions Diagnostic ===\n";

// Check the Logger's getLogDirectory method
require_once __DIR__ . '/app/lib/Logger.php';

echo "1. Checking Logger's log directory detection...\n";

try {
    $logDir = \App\Logger::getLogDirectory();
    echo "✅ Logger detected log directory: $logDir\n";
} catch (Exception $e) {
    echo "❌ Logger failed to detect log directory: " . $e->getMessage() . "\n";
    $logDir = null;
}

echo "\n2. Checking possible log directory paths...\n";

$possiblePaths = [
    __DIR__ . '/app/logs',
    __DIR__ . '/logs',
    '/var/www/html/app/logs',
    '/var/www/html/logs',
    '/var/log/event-manager',
    '/tmp/event-manager-logs'
];

foreach ($possiblePaths as $path) {
    echo "Checking: $path\n";
    
    if (file_exists($path)) {
        if (is_dir($path)) {
            echo "  ✅ Directory exists\n";
            echo "  📁 Directory permissions: " . decoct(fileperms($path) & 0777) . "\n";
            echo "  📖 Directory readable: " . (is_readable($path) ? "✅ YES" : "❌ NO") . "\n";
            echo "  ✏️  Directory writable: " . (is_writable($path) ? "✅ YES" : "❌ NO") . "\n";
            
            // Check ownership
            $owner = posix_getpwuid(fileowner($path));
            $group = posix_getgrgid(filegroup($path));
            echo "  👤 Owner: " . ($owner['name'] ?? 'unknown') . "\n";
            echo "  👥 Group: " . ($group['name'] ?? 'unknown') . "\n";
            
            // List files in directory
            $files = glob($path . '/event-manager-*.log');
            echo "  📄 Log files found: " . count($files) . "\n";
            
            foreach ($files as $file) {
                echo "    - " . basename($file) . " (" . filesize($file) . " bytes)\n";
                echo "      Readable: " . (is_readable($file) ? "✅ YES" : "❌ NO") . "\n";
                echo "      Writable: " . (is_writable($file) ? "✅ YES" : "❌ NO") . "\n";
            }
        } else {
            echo "  ❌ Path exists but is not a directory\n";
        }
    } else {
        echo "  ❌ Path does not exist\n";
        
        // Check if parent directory is writable
        $parentDir = dirname($path);
        if (file_exists($parentDir) && is_writable($parentDir)) {
            echo "  ✅ Parent directory is writable, could create: $path\n";
        } else {
            echo "  ❌ Parent directory not writable: $parentDir\n";
        }
    }
    echo "\n";
}

echo "3. Testing Logger methods...\n";

try {
    $logFiles = \App\Logger::getLogFiles();
    echo "✅ getLogFiles() returned " . count($logFiles) . " files\n";
    
    foreach ($logFiles as $file) {
        echo "  - " . basename($file) . "\n";
    }
} catch (Exception $e) {
    echo "❌ getLogFiles() failed: " . $e->getMessage() . "\n";
}

try {
    $logDirPublic = \App\Logger::getLogDirectoryPublic();
    echo "✅ getLogDirectoryPublic() returned: $logDirPublic\n";
} catch (Exception $e) {
    echo "❌ getLogDirectoryPublic() failed: " . $e->getMessage() . "\n";
}

echo "\n4. Testing log file operations...\n";

try {
    // Test writing to log
    \App\Logger::debug('test_log_write', 'diagnostic', null, 'Testing log write capability');
    echo "✅ Log write test completed\n";
} catch (Exception $e) {
    echo "❌ Log write test failed: " . $e->getMessage() . "\n";
}

echo "\n5. Web server user information...\n";

// Get current user
$currentUser = posix_getpwuid(posix_geteuid());
echo "Current user: " . ($currentUser['name'] ?? 'unknown') . "\n";

// Check if running as web server user
$webUsers = ['www-data', 'apache', 'nginx', 'httpd'];
$isWebUser = in_array($currentUser['name'] ?? '', $webUsers);
echo "Running as web server user: " . ($isWebUser ? "✅ YES" : "❌ NO") . "\n";

if (!$isWebUser) {
    echo "⚠️  Script is not running as web server user. Results may not reflect actual web server permissions.\n";
}

echo "\n=== Diagnostic Complete ===\n";
echo "If issues are found, run this script with sudo to fix permissions:\n";
echo "sudo php fix_log_permissions.php\n";
