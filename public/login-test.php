<?php
// Simple login page test
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../app/bootstrap.php';
    
    // Simulate the login route
    $authController = new App\Routes\AuthController();
    $authController->loginForm();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
} catch (Error $e) {
    echo "Fatal error: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
}
?>
