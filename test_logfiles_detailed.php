<?php
declare(strict_types=1);

echo "=== Detailed LogFiles Method Test ===\n";

// Test 1: Check if we can load the bootstrap
echo "1. Testing bootstrap loading...\n";
try {
    require_once __DIR__ . '/app/bootstrap.php';
    echo "✅ Bootstrap loaded successfully\n";
} catch (Exception $e) {
    echo "❌ Bootstrap failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check if AdminController class exists
echo "2. Testing AdminController class...\n";
if (class_exists('App\Routes\AdminController')) {
    echo "✅ AdminController class exists\n";
} else {
    echo "❌ AdminController class not found\n";
    exit(1);
}

// Test 3: Check if logFiles method exists
echo "3. Testing logFiles method...\n";
if (method_exists('App\Routes\AdminController', 'logFiles')) {
    echo "✅ logFiles method exists\n";
} else {
    echo "❌ logFiles method not found\n";
    exit(1);
}

// Test 4: Test individual function calls that might be used in logFiles
echo "4. Testing function availability...\n";
$functions_to_test = [
    'url', 'csrf_field', 'require_login', 'is_logged_in', 
    'current_user', 'is_organizer', 'require_organizer'
];

foreach ($functions_to_test as $func) {
    if (function_exists($func)) {
        echo "✅ $func() function exists\n";
    } else {
        echo "❌ $func() function missing\n";
    }
}

// Test 5: Test Logger class and methods
echo "5. Testing Logger class...\n";
if (class_exists('App\Logger')) {
    echo "✅ Logger class exists\n";
    
    try {
        $logFiles = \App\Logger::getLogFiles();
        echo "✅ Logger::getLogFiles() = " . count($logFiles) . " files\n";
    } catch (Exception $e) {
        echo "❌ Logger::getLogFiles() failed: " . $e->getMessage() . "\n";
    }
    
    try {
        $logDir = \App\Logger::getLogDirectoryPublic();
        echo "✅ Logger::getLogDirectoryPublic() = $logDir\n";
    } catch (Exception $e) {
        echo "❌ Logger::getLogDirectoryPublic() failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Logger class not found\n";
}

// Test 6: Try to instantiate AdminController and call logFiles
echo "6. Testing AdminController::logFiles() method...\n";
try {
    $controller = new \App\Routes\AdminController();
    echo "✅ AdminController instantiated\n";
    
    // Mock session data to avoid authentication issues
    $_SESSION['user_id'] = 'test-user';
    $_SESSION['user_role'] = 'organizer';
    
    // Capture output
    ob_start();
    $controller->logFiles();
    $output = ob_get_clean();
    
    if (strlen($output) > 0) {
        echo "✅ logFiles() method executed successfully\n";
        echo "Output length: " . strlen($output) . " characters\n";
    } else {
        echo "❌ logFiles() method produced no output\n";
    }
    
} catch (Exception $e) {
    echo "❌ AdminController::logFiles() failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
