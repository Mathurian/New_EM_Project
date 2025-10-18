<?php
// home-debug.php - Debug script for home page issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Home Page Debug</h1>\n";

$projectRoot = dirname(__DIR__);

echo "<h2>1. Loading Bootstrap</h2>\n";
try {
    require_once $projectRoot . '/app/bootstrap.php';
    echo "✅ Bootstrap loaded<br>\n";
} catch (Exception $e) {
    echo "❌ Bootstrap failed: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
    exit;
}

echo "<h2>2. Testing Helper Functions</h2>\n";
try {
    $isLoggedIn = is_logged_in();
    echo "✅ is_logged_in() function works<br>\n";
    echo "Result: " . ($isLoggedIn ? 'true' : 'false') . "<br>\n";
} catch (Exception $e) {
    echo "❌ is_logged_in() failed: " . $e->getMessage() . "<br>\n";
}

try {
    $csrfField = App\csrf_field();
    echo "✅ App\\csrf_field() function works<br>\n";
    echo "Output: " . htmlspecialchars($csrfField) . "<br>\n";
} catch (Exception $e) {
    echo "❌ App\\csrf_field() failed: " . $e->getMessage() . "<br>\n";
}

echo "<h2>3. Testing HomeController</h2>\n";
try {
    $homeController = new App\Routes\HomeController();
    echo "✅ HomeController instantiated<br>\n";
} catch (Exception $e) {
    echo "❌ HomeController failed: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>4. Testing Home View Directly</h2>\n";
ob_start();
try {
    App\view('home', ['title' => 'Debug Test']);
    $homeOutput = ob_get_clean();
    echo "✅ Home view loaded successfully<br>\n";
    echo "Output length: " . strlen($homeOutput) . " characters<br>\n";
    echo "<h3>Home View Preview:</h3>\n";
    echo "<div style='border: 1px solid #ccc; padding: 10px; max-height: 300px; overflow-y: auto;'>\n";
    echo htmlspecialchars(substr($homeOutput, 0, 1000));
    if (strlen($homeOutput) > 1000) {
        echo "\n... (truncated)";
    }
    echo "</div>\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Home view failed: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>5. Testing HomeController index()</h2>\n";
try {
    $homeController = new App\Routes\HomeController();
    ob_start();
    $homeController->index();
    $controllerOutput = ob_get_clean();
    echo "✅ HomeController index() executed successfully<br>\n";
    echo "Output length: " . strlen($controllerOutput) . " characters<br>\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ HomeController index() failed: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>Debug Complete</h2>\n";
?>
