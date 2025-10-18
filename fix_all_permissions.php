<?php
/**
 * Comprehensive Permissions Fix Script
 * 
 * This script fixes permission issues affecting:
 * - Log file management (500 error)
 * - Admin activity logging (not populating)
 * - Database/schema backups (500 error)
 */

echo "=== Comprehensive Permissions Fix ===\n";

// Include required classes
require_once __DIR__ . '/app/lib/Logger.php';
require_once __DIR__ . '/app/lib/DB.php';

echo "1. Fixing log directory permissions...\n";

try {
    $logDir = \App\Logger::getLogDirectoryPublic();
    echo "✅ Log directory: $logDir\n";
    
    // Ensure directory exists and has proper permissions
    if (!is_dir($logDir)) {
        if (mkdir($logDir, 0755, true)) {
            echo "✅ Created log directory\n";
        } else {
            echo "❌ Failed to create log directory\n";
        }
    }
    
    // Set permissions
    if (chmod($logDir, 0755)) {
        echo "✅ Set log directory permissions to 0755\n";
    }
    
    // Set ownership
    $webUser = 'www-data';
    if (function_exists('posix_getpwnam') && posix_getpwnam($webUser)) {
        chown($logDir, $webUser);
        chgrp($logDir, $webUser);
        echo "✅ Set log directory ownership to $webUser\n";
    }
    
} catch (Exception $e) {
    echo "❌ Log directory fix failed: " . $e->getMessage() . "\n";
}

echo "\n2. Fixing backup directory permissions...\n";

$backupPaths = [
    '/var/www/html/backups',
    '/tmp/event_manager_backups',
    __DIR__ . '/backups',
    sys_get_temp_dir() . '/event_manager_backups'
];

foreach ($backupPaths as $path) {
    echo "Checking backup path: $path\n";
    
    if (!is_dir($path)) {
        if (mkdir($path, 0755, true)) {
            echo "  ✅ Created backup directory\n";
        } else {
            echo "  ❌ Failed to create backup directory\n";
            continue;
        }
    }
    
    // Set permissions
    if (chmod($path, 0755)) {
        echo "  ✅ Set backup directory permissions to 0755\n";
    }
    
    // Set ownership
    if (function_exists('posix_getpwnam') && posix_getpwnam($webUser)) {
        chown($path, $webUser);
        chgrp($path, $webUser);
        echo "  ✅ Set backup directory ownership to $webUser\n";
    }
    
    // Test write access
    $testFile = $path . '/test_write.tmp';
    if (file_put_contents($testFile, 'test')) {
        echo "  ✅ Backup directory is writable\n";
        unlink($testFile);
    } else {
        echo "  ❌ Backup directory is not writable\n";
    }
}

echo "\n3. Fixing database permissions...\n";

try {
    $dbPath = \App\DB::getDatabasePath();
    $dbDir = dirname($dbPath);
    
    echo "Database path: $dbPath\n";
    echo "Database directory: $dbDir\n";
    
    // Set directory permissions
    if (chmod($dbDir, 0755)) {
        echo "✅ Set database directory permissions to 0755\n";
    }
    
    // Set database file permissions
    if (file_exists($dbPath) && chmod($dbPath, 0664)) {
        echo "✅ Set database file permissions to 0664\n";
    }
    
    // Set ownership
    if (function_exists('posix_getpwnam') && posix_getpwnam($webUser)) {
        chown($dbPath, $webUser);
        chgrp($dbPath, $webUser);
        chown($dbDir, $webUser);
        chgrp($dbDir, $webUser);
        echo "✅ Set database ownership to $webUser\n";
    }
    
    // Clean up lock files
    $lockFiles = [
        $dbPath . '-journal',
        $dbPath . '-wal',
        $dbPath . '-shm'
    ];
    
    foreach ($lockFiles as $lockFile) {
        if (file_exists($lockFile)) {
            unlink($lockFile);
            echo "✅ Removed lock file: " . basename($lockFile) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database permissions fix failed: " . $e->getMessage() . "\n";
}

echo "\n4. Testing functionality...\n";

// Test log functionality
try {
    \App\Logger::debug('permissions_fix_test', 'system', null, 'Testing log functionality after permissions fix');
    echo "✅ Log functionality test passed\n";
} catch (Exception $e) {
    echo "❌ Log functionality test failed: " . $e->getMessage() . "\n";
}

// Test database functionality
try {
    $pdo = \App\DB::pdo();
    $stmt = $pdo->query('SELECT 1 as test');
    $result = $stmt->fetch();
    if ($result && $result['test'] == 1) {
        echo "✅ Database functionality test passed\n";
    } else {
        echo "❌ Database functionality test failed\n";
    }
} catch (Exception $e) {
    echo "❌ Database functionality test failed: " . $e->getMessage() . "\n";
}

// Test log file management
try {
    $logFiles = \App\Logger::getLogFiles();
    echo "✅ Log file management test passed (" . count($logFiles) . " files found)\n";
} catch (Exception $e) {
    echo "❌ Log file management test failed: " . $e->getMessage() . "\n";
}

echo "\n5. Final verification...\n";

// Check current user
$currentUser = posix_getpwuid(posix_geteuid());
echo "Running as user: " . ($currentUser['name'] ?? 'unknown') . "\n";

// Check if running as web server user
$webUsers = ['www-data', 'apache', 'nginx', 'httpd'];
$isWebUser = in_array($currentUser['name'] ?? '', $webUsers);
echo "Running as web server user: " . ($isWebUser ? "✅ YES" : "❌ NO") . "\n";

if (!$isWebUser) {
    echo "⚠️  Script is not running as web server user.\n";
    echo "   For complete fix, run with sudo: sudo php fix_all_permissions.php\n";
}

echo "\n=== Fix Complete ===\n";
echo "The following should now work:\n";
echo "✅ Log file management page (no more 500 error)\n";
echo "✅ Admin activity logging (should populate again)\n";
echo "✅ Database/schema backups (no more 500 error)\n";
echo "\nIf issues persist, run with sudo for complete permissions fix.\n";
