<?php
/**
 * Test Bootstrap Loading
 * 
 * This script tests if the bootstrap and helper functions are loading correctly
 * in the web context.
 */

echo "=== Testing Bootstrap Loading ===\n";

echo "1. Testing bootstrap loading...\n";
try {
    require_once __DIR__ . '/app/bootstrap.php';
    echo "✅ Bootstrap loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Bootstrap loading failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n2. Testing helper functions...\n";
try {
    // Test if functions exist
    $functions = ['url', 'csrf_field', 'is_logged_in', 'current_user', 'is_organizer'];
    
    foreach ($functions as $func) {
        if (function_exists($func)) {
            echo "✅ $func() function exists\n";
        } else {
            echo "❌ $func() function does NOT exist\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Helper function test failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n3. Testing specific functions...\n";
try {
    // Test url function
    $testUrl = url('admin/log-files');
    echo "✅ url() function works: $testUrl\n";
    
    // Test csrf_field function
    $csrfField = csrf_field();
    echo "✅ csrf_field() function works: " . (strlen($csrfField) > 0 ? "YES" : "NO") . "\n";
    
} catch (Exception $e) {
    echo "❌ Specific function test failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n4. Testing Logger after bootstrap...\n";
try {
    $logFiles = \App\Logger::getLogFiles();
    echo "✅ Logger works after bootstrap: " . count($logFiles) . " files\n";
    
} catch (Exception $e) {
    echo "❌ Logger test failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
