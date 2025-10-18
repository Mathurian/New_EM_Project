<?php
// Test the csrf_field fix
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>CSRF Field Test</h1>\n";

try {
    echo "<h2>1. Loading Bootstrap</h2>\n";
    require_once '../app/bootstrap.php';
    echo "✅ Bootstrap loaded<br>\n";
    
    echo "<h2>2. Testing CSRF Field Function</h2>\n";
    $csrfField = App\csrf_field();
    echo "✅ csrf_field() function works<br>\n";
    echo "Output: " . htmlspecialchars($csrfField) . "<br>\n";
    
    echo "<h2>3. Testing Login Template Directly</h2>\n";
    ob_start();
    try {
        include '../app/views/auth/login.php';
        $loginOutput = ob_get_clean();
        echo "✅ Login template loaded successfully<br>\n";
        echo "Output length: " . strlen($loginOutput) . " characters<br>\n";
        echo "<h3>Login Template Preview:</h3>\n";
        echo "<div style='border: 1px solid #ccc; padding: 10px; max-height: 300px; overflow-y: auto;'>\n";
        echo htmlspecialchars(substr($loginOutput, 0, 1000));
        if (strlen($loginOutput) > 1000) {
            echo "\n... (truncated)";
        }
        echo "</div>\n";
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Login template failed: " . $e->getMessage() . "<br>\n";
    }
    
    echo "<h2>4. Testing AuthController loginForm</h2>\n";
    $authController = new App\Routes\AuthController();
    ob_start();
    try {
        $authController->loginForm();
        $methodOutput = ob_get_clean();
        echo "✅ loginForm method executed successfully<br>\n";
        echo "Output length: " . strlen($methodOutput) . " characters<br>\n";
        
        if (strlen($methodOutput) > 0) {
            echo "<h3>Method Output Preview:</h3>\n";
            echo "<div style='border: 1px solid #ccc; padding: 10px; max-height: 300px; overflow-y: auto;'>\n";
            echo htmlspecialchars(substr($methodOutput, 0, 1000));
            if (strlen($methodOutput) > 1000) {
                echo "\n... (truncated)";
            }
            echo "</div>\n";
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ loginForm method failed: " . $e->getMessage() . "<br>\n";
        echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error occurred: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
} catch (Error $e) {
    echo "❌ Fatal error occurred: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>Test Complete</h2>\n";
?>
