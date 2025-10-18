<?php
/**
 * Test Log Files Management Page Components
 * 
 * This script tests the individual components that the log files management page uses
 * to isolate where the 500 error is occurring.
 */

echo "=== Testing Log Files Management Components ===\n";

// Test 1: Check if we can load the Logger class
echo "1. Testing Logger class loading...\n";
try {
    require_once __DIR__ . '/app/lib/Logger.php';
    echo "✅ Logger class loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Failed to load Logger class: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Test getLogFiles() method
echo "\n2. Testing getLogFiles() method...\n";
try {
    $logFiles = \App\Logger::getLogFiles();
    echo "✅ getLogFiles() returned " . count($logFiles) . " files\n";
    
    foreach ($logFiles as $file) {
        echo "  - " . basename($file) . "\n";
    }
} catch (Exception $e) {
    echo "❌ getLogFiles() failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Test 3: Test getLogDirectoryPublic() method
echo "\n3. Testing getLogDirectoryPublic() method...\n";
try {
    $logDirectory = \App\Logger::getLogDirectoryPublic();
    echo "✅ getLogDirectoryPublic() returned: $logDirectory\n";
} catch (Exception $e) {
    echo "❌ getLogDirectoryPublic() failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Test 4: Test file operations on log files
echo "\n4. Testing file operations...\n";
try {
    $logFiles = \App\Logger::getLogFiles();
    if (!empty($logFiles)) {
        $file = $logFiles[0];
        echo "Testing file: " . basename($file) . "\n";
        
        echo "  - File exists: " . (file_exists($file) ? "✅ YES" : "❌ NO") . "\n";
        echo "  - File readable: " . (is_readable($file) ? "✅ YES" : "❌ NO") . "\n";
        echo "  - File size: " . filesize($file) . " bytes\n";
        echo "  - File modified: " . date('Y-m-d H:i:s', filemtime($file)) . "\n";
        
        // Test basename function
        echo "  - basename(): " . basename($file) . "\n";
        
    } else {
        echo "⚠️  No log files found to test\n";
    }
} catch (Exception $e) {
    echo "❌ File operations failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Test 5: Test helper functions
echo "\n5. Testing helper functions...\n";
try {
    require_once __DIR__ . '/app/lib/helpers.php';
    echo "✅ Helper functions loaded\n";
    
    // Test url function
    $testUrl = url('admin/log-files');
    echo "✅ url() function works: $testUrl\n";
    
    // Test csrf_field function
    $csrfField = csrf_field();
    echo "✅ csrf_field() function works: " . (strlen($csrfField) > 0 ? "YES" : "NO") . "\n";
    
} catch (Exception $e) {
    echo "❌ Helper functions failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Test 6: Test view rendering (simplified)
echo "\n6. Testing view rendering components...\n";
try {
    $logFiles = \App\Logger::getLogFiles();
    $logDirectory = \App\Logger::getLogDirectoryPublic();
    
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
    
    // Test htmlspecialchars function
    if (!empty($fileInfo)) {
        $testString = htmlspecialchars($fileInfo[0]['filename']);
        echo "✅ htmlspecialchars() works: $testString\n";
    }
    
} catch (Exception $e) {
    echo "❌ View rendering components failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "If any tests failed, that's likely the source of the 500 error.\n";
