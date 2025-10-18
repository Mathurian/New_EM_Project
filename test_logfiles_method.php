<?php
/**
 * Simple Test for Log Files Method
 * 
 * This script mimics exactly what the AdminController::logFiles() method does
 * to identify where the 500 error is occurring.
 */

echo "=== Testing AdminController::logFiles() Method ===\n";

// Start session (needed for authentication)
session_start();

echo "1. Testing authentication...\n";
try {
    require_once __DIR__ . '/app/lib/helpers.php';
    
    $isLoggedIn = is_logged_in();
    $currentUser = current_user();
    $isOrganizer = is_organizer();
    
    echo "  - is_logged_in(): " . ($isLoggedIn ? "✅ YES" : "❌ NO") . "\n";
    echo "  - current_user(): " . ($currentUser ? "✅ " . ($currentUser['role'] ?? 'unknown') : "❌ NULL") . "\n";
    echo "  - is_organizer(): " . ($isOrganizer ? "✅ YES" : "❌ NO") . "\n";
    
    if (!$isOrganizer) {
        echo "⚠️  User is not an organizer - this might cause authentication issues\n";
    }
    
} catch (Exception $e) {
    echo "❌ Authentication test failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n2. Testing Logger methods (exactly as in logFiles method)...\n";
try {
    require_once __DIR__ . '/app/lib/Logger.php';
    
    // This is exactly what the logFiles method does
    $logFiles = \App\Logger::getLogFiles();
    $logDirectory = \App\Logger::getLogDirectoryPublic();
    
    echo "✅ getLogFiles() returned " . count($logFiles) . " files\n";
    echo "✅ getLogDirectoryPublic() returned: $logDirectory\n";
    
} catch (Exception $e) {
    echo "❌ Logger methods failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n3. Testing file info creation (exactly as in logFiles method)...\n";
try {
    $logFiles = \App\Logger::getLogFiles();
    $logDirectory = \App\Logger::getLogDirectoryPublic();
    
    // This is exactly what the logFiles method does
    $fileInfo = [];
    foreach ($logFiles as $file) {
        $fileInfo[] = [
            'filename' => basename($file),
            'path' => $file,
            'size' => filesize($file),
            'modified' => filemtime($file),
            'readable' => is_readable($file)
        ];
    }
    
    echo "✅ File info array created with " . count($fileInfo) . " entries\n";
    
    if (!empty($fileInfo)) {
        echo "Sample file info:\n";
        $sample = $fileInfo[0];
        echo "  - Filename: " . $sample['filename'] . "\n";
        echo "  - Size: " . $sample['size'] . " bytes\n";
        echo "  - Modified: " . date('Y-m-d H:i:s', $sample['modified']) . "\n";
        echo "  - Readable: " . ($sample['readable'] ? "YES" : "NO") . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ File info creation failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n4. Testing view function...\n";
try {
    // Test if the view function exists and works
    if (function_exists('view')) {
        echo "✅ view() function exists\n";
    } else {
        echo "❌ view() function does not exist\n";
    }
    
    // Test if we can call render_to_string
    if (function_exists('render_to_string')) {
        echo "✅ render_to_string() function exists\n";
    } else {
        echo "❌ render_to_string() function does not exist\n";
    }
    
} catch (Exception $e) {
    echo "❌ View function test failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "This test mimics exactly what AdminController::logFiles() does.\n";
echo "If any step fails, that's the source of the 500 error.\n";
