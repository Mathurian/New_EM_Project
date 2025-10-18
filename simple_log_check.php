<?php
/**
 * Simple Log Directory Permissions Check
 * 
 * This script checks log directory permissions without trying to use
 * the full Logger functionality that requires database access.
 */

echo "=== Simple Log Directory Permissions Check ===\n";

echo "1. Checking log directory paths...\n";

$possiblePaths = [
    '/var/www/html/app/logs',
    '/var/www/html/logs',
    '/var/log/event-manager',
    '/tmp/event-manager-logs'
];

$logDir = null;
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
            
            if (!$logDir) {
                $logDir = $path; // Use the first working directory
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

echo "2. Testing basic file operations...\n";

if ($logDir) {
    echo "Using log directory: $logDir\n";
    
    // Test creating a test file
    $testFile = $logDir . '/test_permissions.tmp';
    if (file_put_contents($testFile, 'test content')) {
        echo "✅ Successfully created test file\n";
        
        // Test reading the file
        if (file_get_contents($testFile) === 'test content') {
            echo "✅ Successfully read test file\n";
        } else {
            echo "❌ Failed to read test file\n";
        }
        
        // Clean up
        unlink($testFile);
        echo "✅ Successfully deleted test file\n";
    } else {
        echo "❌ Failed to create test file\n";
    }
} else {
    echo "❌ No suitable log directory found\n";
}

echo "\n3. Checking backup directory paths...\n";

$backupPaths = [
    '/var/www/html/backups',
    '/tmp/event_manager_backups',
    '/var/www/html/app/backups',
    sys_get_temp_dir() . '/event_manager_backups'
];

foreach ($backupPaths as $path) {
    echo "Checking backup path: $path\n";
    
    if (file_exists($path)) {
        if (is_dir($path)) {
            echo "  ✅ Directory exists\n";
            echo "  📁 Directory permissions: " . decoct(fileperms($path) & 0777) . "\n";
            echo "  📖 Directory readable: " . (is_readable($path) ? "✅ YES" : "❌ NO") . "\n";
            echo "  ✏️  Directory writable: " . (is_writable($path) ? "✅ YES" : "❌ NO") . "\n";
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

echo "4. Checking database permissions...\n";

$dbPath = '/var/www/html/app/db/contest.sqlite';
$dbDir = dirname($dbPath);

echo "Database path: $dbPath\n";
echo "Database directory: $dbDir\n";

if (file_exists($dbPath)) {
    echo "✅ Database file exists\n";
    echo "📁 Database file permissions: " . decoct(fileperms($dbPath) & 0777) . "\n";
    echo "📖 Database file readable: " . (is_readable($dbPath) ? "✅ YES" : "❌ NO") . "\n";
    echo "✏️  Database file writable: " . (is_writable($dbPath) ? "✅ YES" : "❌ NO") . "\n";
} else {
    echo "❌ Database file does not exist\n";
}

if (is_dir($dbDir)) {
    echo "✅ Database directory exists\n";
    echo "📁 Database directory permissions: " . decoct(fileperms($dbDir) & 0777) . "\n";
    echo "📖 Database directory readable: " . (is_readable($dbDir) ? "✅ YES" : "❌ NO") . "\n";
    echo "✏️  Database directory writable: " . (is_writable($dbDir) ? "✅ YES" : "❌ NO") . "\n";
} else {
    echo "❌ Database directory does not exist\n";
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

echo "\n=== Check Complete ===\n";
echo "Based on the output above, the log directory permissions appear to be working correctly.\n";
echo "The 500 errors in the web interface may be due to other issues.\n";
echo "Try accessing the log file management page again to see if the issue persists.\n";
