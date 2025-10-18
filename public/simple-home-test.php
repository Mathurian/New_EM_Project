<?php
// simple-home-test.php - Simple test for home page
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Home Page Test</h1>\n";

$projectRoot = dirname(__DIR__);

echo "<h2>1. Loading Bootstrap</h2>\n";
try {
    require_once $projectRoot . '/app/bootstrap.php';
    echo "✅ Bootstrap loaded<br>\n";
} catch (Exception $e) {
    echo "❌ Bootstrap failed: " . $e->getMessage() . "<br>\n";
    exit;
}

echo "<h2>2. Testing Home View</h2>\n";
try {
    ob_start();
    App\view('home', ['title' => 'Test Home']);
    $output = ob_get_clean();
    echo "✅ Home view loaded successfully<br>\n";
    echo "Output length: " . strlen($output) . " characters<br>\n";
    
    // Check if login form is present
    if (strpos($output, 'Login') !== false) {
        echo "✅ Login form found in output<br>\n";
    } else {
        echo "⚠️ Login form not found in output<br>\n";
    }
    
    // Check if CSRF field is present
    if (strpos($output, 'csrf_token') !== false) {
        echo "✅ CSRF field found in output<br>\n";
    } else {
        echo "⚠️ CSRF field not found in output<br>\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Home view failed: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>3. Testing Direct Access</h2>\n";
echo "<p><a href='/'>Go to Home Page</a></p>\n";
echo "<p><a href='/home-debug.php'>Go to Detailed Debug</a></p>\n";

echo "<h2>Test Complete</h2>\n";
?>
