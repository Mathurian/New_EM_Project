<?php
// Detailed login route debugging script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Login Route Debug</h1>\n";

try {
    echo "<h2>1. Loading Bootstrap</h2>\n";
    require_once '../app/bootstrap.php';
    echo "✅ Bootstrap loaded successfully<br>\n";
    
    echo "<h2>2. Testing Database Connection</h2>\n";
    $pdo = App\DB::pdo();
    echo "✅ Database connection successful<br>\n";
    
    echo "<h2>3. Testing Router</h2>\n";
    $router = new App\Router();
    echo "✅ Router created successfully<br>\n";
    
    echo "<h2>4. Testing AuthController</h2>\n";
    $authController = new App\Routes\AuthController();
    echo "✅ AuthController created successfully<br>\n";
    
    echo "<h2>5. Testing loginForm Method</h2>\n";
    ob_start(); // Capture output
    $authController->loginForm();
    $output = ob_get_clean();
    echo "✅ loginForm method executed successfully<br>\n";
    echo "Output length: " . strlen($output) . " characters<br>\n";
    
    if (strlen($output) > 0) {
        echo "<h3>Login Form Output Preview:</h3>\n";
        echo "<div style='border: 1px solid #ccc; padding: 10px; max-height: 300px; overflow-y: auto;'>\n";
        echo htmlspecialchars(substr($output, 0, 1000));
        if (strlen($output) > 1000) {
            echo "\n... (truncated)";
        }
        echo "</div>\n";
    } else {
        echo "❌ No output from loginForm method<br>\n";
    }
    
    echo "<h2>6. Testing View Helper</h2>\n";
    $viewOutput = App\view('auth/login', []);
    echo "✅ View helper executed successfully<br>\n";
    echo "View output length: " . strlen($viewOutput) . " characters<br>\n";
    
    if (strlen($viewOutput) > 0) {
        echo "<h3>View Output Preview:</h3>\n";
        echo "<div style='border: 1px solid #ccc; padding: 10px; max-height: 300px; overflow-y: auto;'>\n";
        echo htmlspecialchars(substr($viewOutput, 0, 1000));
        if (strlen($viewOutput) > 1000) {
            echo "\n... (truncated)";
        }
        echo "</div>\n";
    } else {
        echo "❌ No output from view helper<br>\n";
    }
    
    echo "<h2>7. Testing Layout</h2>\n";
    $layoutFile = '../app/views/partials/layout.php';
    if (file_exists($layoutFile)) {
        echo "✅ Layout file exists<br>\n";
        echo "Layout file readable: " . (is_readable($layoutFile) ? "YES" : "NO") . "<br>\n";
        
        // Test if layout can be included
        $template = 'auth/login';
        $templateName = 'auth/login';
        $templateFile = '../app/views/auth/login.php';
        
        if (file_exists($templateFile)) {
            echo "✅ Template file exists<br>\n";
            echo "Template file readable: " . (is_readable($templateFile) ? "YES" : "NO") . "<br>\n";
        } else {
            echo "❌ Template file missing: $templateFile<br>\n";
        }
    } else {
        echo "❌ Layout file missing: $layoutFile<br>\n";
    }
    
    echo "<h2>8. Testing Session</h2>\n";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    echo "✅ Session started<br>\n";
    echo "Session ID: " . session_id() . "<br>\n";
    
    echo "<h2>9. Testing Error Handler</h2>\n";
    $errorHandler = new App\ErrorHandler();
    echo "✅ ErrorHandler created successfully<br>\n";
    
} catch (Exception $e) {
    echo "❌ Error occurred: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
} catch (Error $e) {
    echo "❌ Fatal error occurred: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>Debug Complete</h2>\n";
?>
