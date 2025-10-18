<?php
/**
 * Fix Log Directory and File Permissions Script
 * 
 * This script fixes permission issues with the log directory and files
 * that could be causing the 500 error on the log file management page.
 */

echo "=== Log Directory and File Permissions Fix ===\n";

// Check the Logger's getLogDirectory method
require_once __DIR__ . '/app/lib/Logger.php';

echo "1. Detecting log directory...\n";

try {
    $logDir = \App\Logger::getLogDirectoryPublic();
    echo "✅ Logger detected log directory: $logDir\n";
} catch (Exception $e) {
    echo "❌ Logger failed to detect log directory: " . $e->getMessage() . "\n";
    
    // Try to create the most likely log directory
    $logDir = __DIR__ . '/app/logs';
    echo "Attempting to create log directory: $logDir\n";
    
    if (!is_dir($logDir)) {
        if (mkdir($logDir, 0755, true)) {
            echo "✅ Created log directory: $logDir\n";
        } else {
            echo "❌ Failed to create log directory: $logDir\n";
            exit(1);
        }
    }
}

echo "\n2. Setting log directory permissions...\n";

// Set directory permissions
if (chmod($logDir, 0755)) {
    echo "✅ Set directory permissions to 0755\n";
} else {
    echo "❌ Failed to set directory permissions\n";
}

// Try to set ownership to www-data
$webUser = 'www-data';
$webGroup = 'www-data';

if (function_exists('posix_getpwnam') && posix_getpwnam($webUser)) {
    if (chown($logDir, $webUser)) {
        echo "✅ Set directory ownership to $webUser\n";
    } else {
        echo "⚠️  Could not change ownership to $webUser (may need sudo)\n";
    }
    
    if (chgrp($logDir, $webGroup)) {
        echo "✅ Set directory group to $webGroup\n";
    } else {
        echo "⚠️  Could not change group to $webGroup (may need sudo)\n";
    }
} else {
    echo "⚠️  Web user '$webUser' not found, skipping ownership change\n";
}

echo "\n3. Checking and fixing log files...\n";

$logFiles = glob($logDir . '/event-manager-*.log');

if (empty($logFiles)) {
    echo "No log files found, creating a test log file...\n";
    
    $testLogFile = $logDir . '/event-manager-' . date('Y-m-d') . '.log';
    if (file_put_contents($testLogFile, "Test log entry created at " . date('Y-m-d H:i:s') . "\n")) {
        echo "✅ Created test log file: " . basename($testLogFile) . "\n";
        $logFiles = [$testLogFile];
    } else {
        echo "❌ Failed to create test log file\n";
    }
}

foreach ($logFiles as $logFile) {
    echo "Processing: " . basename($logFile) . "\n";
    
    // Set file permissions
    if (chmod($logFile, 0664)) {
        echo "  ✅ Set file permissions to 0664\n";
    } else {
        echo "  ❌ Failed to set file permissions\n";
    }
    
    // Set file ownership
    if (function_exists('posix_getpwnam') && posix_getpwnam($webUser)) {
        if (chown($logFile, $webUser)) {
            echo "  ✅ Set file ownership to $webUser\n";
        } else {
            echo "  ⚠️  Could not change file ownership (may need sudo)\n";
        }
        
        if (chgrp($logFile, $webGroup)) {
            echo "  ✅ Set file group to $webGroup\n";
        } else {
            echo "  ⚠️  Could not change file group (may need sudo)\n";
        }
    }
    
    // Test file access
    echo "  📖 File readable: " . (is_readable($logFile) ? "✅ YES" : "❌ NO") . "\n";
    echo "  ✏️  File writable: " . (is_writable($logFile) ? "✅ YES" : "❌ NO") . "\n";
}

echo "\n4. Testing Logger functionality...\n";

try {
    // Test getting log files
    $files = \App\Logger::getLogFiles();
    echo "✅ getLogFiles() returned " . count($files) . " files\n";
    
    // Test getting log directory
    $dir = \App\Logger::getLogDirectoryPublic();
    echo "✅ getLogDirectoryPublic() returned: $dir\n";
    
    // Test writing to log
    \App\Logger::debug('permissions_fix_test', 'system', null, 'Testing log write after permissions fix');
    echo "✅ Log write test completed\n";
    
} catch (Exception $e) {
    echo "❌ Logger functionality test failed: " . $e->getMessage() . "\n";
}

echo "\n5. Final verification...\n";

// Check final permissions
$finalDirPerms = fileperms($logDir);
echo "Final directory permissions: " . decoct($finalDirPerms & 0777) . "\n";
echo "Directory is writable: " . (is_writable($logDir) ? "✅ YES" : "❌ NO") . "\n";

if (!empty($logFiles)) {
    $finalFilePerms = fileperms($logFiles[0]);
    echo "Final file permissions: " . decoct($finalFilePerms & 0777) . "\n";
    echo "File is readable: " . (is_readable($logFiles[0]) ? "✅ YES" : "❌ NO") . "\n";
    echo "File is writable: " . (is_writable($logFiles[0]) ? "✅ YES" : "❌ NO") . "\n";
}

echo "\n=== Fix Complete ===\n";
echo "The log file management page should now work correctly.\n";
echo "If issues persist, run with sudo: sudo php fix_log_permissions.php\n";
