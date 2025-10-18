<?php
// Simple login test script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Login Test</h1>\n";

try {
    echo "<h2>1. Loading Bootstrap</h2>\n";
    require_once '../app/bootstrap.php';
    echo "✅ Bootstrap loaded<br>\n";
    
    echo "<h2>2. Starting Session</h2>\n";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "✅ Session started<br>\n";
    echo "Session ID: " . session_id() . "<br>\n";
    
    echo "<h2>3. Testing CSRF Token</h2>\n";
    $token = App\SecurityService::generateCsrfToken();
    echo "✅ CSRF token generated: " . substr($token, 0, 10) . "...<br>\n";
    
    echo "<h2>4. Testing CSRF Field</h2>\n";
    $csrfField = App\csrf_field();
    echo "✅ CSRF field generated<br>\n";
    echo "Field preview: " . htmlspecialchars(substr($csrfField, 0, 50)) . "...<br>\n";
    
    echo "<h2>5. Testing View Helper</h2>\n";
    $template = 'auth/login';
    $templateFile = '../app/views/' . $template . '.php';
    echo "Template file: $templateFile<br>\n";
    echo "File exists: " . (file_exists($templateFile) ? "YES" : "NO") . "<br>\n";
    echo "File readable: " . (is_readable($templateFile) ? "YES" : "NO") . "<br>\n";
    
    echo "<h2>6. Testing Layout File</h2>\n";
    $layoutFile = '../app/views/partials/layout.php';
    echo "Layout file: $layoutFile<br>\n";
    echo "File exists: " . (file_exists($layoutFile) ? "YES" : "NO") . "<br>\n";
    echo "File readable: " . (is_readable($layoutFile) ? "YES" : "NO") . "<br>\n";
    
    echo "<h2>7. Testing Direct Template Include</h2>\n";
    ob_start();
    try {
        include $templateFile;
        $templateOutput = ob_get_clean();
        echo "✅ Template included successfully<br>\n";
        echo "Output length: " . strlen($templateOutput) . " characters<br>\n";
        echo "<h3>Template Output Preview:</h3>\n";
        echo "<div style='border: 1px solid #ccc; padding: 10px; max-height: 200px; overflow-y: auto;'>\n";
        echo htmlspecialchars(substr($templateOutput, 0, 500));
        if (strlen($templateOutput) > 500) {
            echo "\n... (truncated)";
        }
        echo "</div>\n";
    } catch (Exception $e) {
        ob_end_clean();
        echo "❌ Template include failed: " . $e->getMessage() . "<br>\n";
    }
    
    echo "<h2>8. Testing AuthController</h2>\n";
    $authController = new App\Routes\AuthController();
    echo "✅ AuthController created<br>\n";
    
    echo "<h2>9. Testing loginForm Method</h2>\n";
    ob_start();
    try {
        $authController->loginForm();
        $methodOutput = ob_get_clean();
        echo "✅ loginForm method executed<br>\n";
        echo "Output length: " . strlen($methodOutput) . " characters<br>\n";
        
        if (strlen($methodOutput) > 0) {
            echo "<h3>Method Output Preview:</h3>\n";
            echo "<div style='border: 1px solid #ccc; padding: 10px; max-height: 200px; overflow-y: auto;'>\n";
            echo htmlspecialchars(substr($methodOutput, 0, 500));
            if (strlen($methodOutput) > 500) {
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
