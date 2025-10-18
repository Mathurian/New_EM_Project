<?php
/**
 * Test Web Application Bootstrap
 * 
 * This script mimics exactly what the web application does when loading
 * to identify where the helper functions are failing.
 */

echo "=== Testing Web Application Bootstrap ===\n";

echo "1. Testing public/index.php bootstrap loading...\n";
try {
    // This is exactly what public/index.php does
    require __DIR__ . '/app/bootstrap.php';
    echo "✅ Bootstrap loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Bootstrap loading failed: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
} catch (Error $e) {
    echo "❌ Bootstrap loading failed with Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n2. Testing if helper functions are available...\n";
$functions = [
    'url' => 'url',
    'csrf_field' => 'csrf_field', 
    'csrf_token' => 'csrf_token',
    'is_logged_in' => 'is_logged_in',
    'current_user' => 'current_user',
    'is_organizer' => 'is_organizer',
    'require_organizer' => 'require_organizer'
];

foreach ($functions as $name => $func) {
    if (function_exists($func)) {
        echo "✅ $name() function exists\n";
    } else {
        echo "❌ $name() function does NOT exist\n";
    }
}

echo "\n3. Testing specific function calls...\n";
try {
    $testUrl = url('admin/log-files');
    echo "✅ url('admin/log-files') = $testUrl\n";
} catch (Exception $e) {
    echo "❌ url() function failed: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ url() function failed with Error: " . $e->getMessage() . "\n";
}

try {
    $csrfToken = csrf_token();
    echo "✅ csrf_token() = " . (strlen($csrfToken) > 0 ? "SUCCESS" : "EMPTY") . "\n";
} catch (Exception $e) {
    echo "❌ csrf_token() function failed: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ csrf_token() function failed with Error: " . $e->getMessage() . "\n";
}

try {
    $csrfField = csrf_field();
    echo "✅ csrf_field() = " . (strlen($csrfField) > 0 ? "SUCCESS" : "EMPTY") . "\n";
} catch (Exception $e) {
    echo "❌ csrf_field() function failed: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ csrf_field() function failed with Error: " . $e->getMessage() . "\n";
}

echo "\n4. Testing Logger after full bootstrap...\n";
try {
    $logFiles = \App\Logger::getLogFiles();
    echo "✅ Logger::getLogFiles() = " . count($logFiles) . " files\n";
    
    $logDir = \App\Logger::getLogDirectoryPublic();
    echo "✅ Logger::getLogDirectoryPublic() = $logDir\n";
    
} catch (Exception $e) {
    echo "❌ Logger methods failed: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Logger methods failed with Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "This test mimics exactly what the web application does.\n";
echo "If any functions are missing, that's the source of the 500 error.\n";
