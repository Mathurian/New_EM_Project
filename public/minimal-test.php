<?php
// Minimal test to isolate the 500 error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Minimal Test</h1>\n";

echo "<h2>Test 1: Basic PHP</h2>\n";
echo "PHP is working!<br>\n";

echo "<h2>Test 2: File Includes</h2>\n";
try {
    require_once 'app/lib/helpers.php';
    echo "helpers.php loaded successfully<br>\n";
} catch (Exception $e) {
    echo "helpers.php failed: " . $e->getMessage() . "<br>\n";
}

echo "<h2>Test 3: Database Connection</h2>\n";
try {
    require_once 'app/lib/DB.php';
    echo "DB.php loaded successfully<br>\n";
    
    $pdo = App\DB::pdo();
    echo "Database connection successful<br>\n";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "<br>\n";
}

echo "<h2>Test 4: FrontendOptimizer</h2>\n";
try {
    require_once 'app/lib/FrontendOptimizer.php';
    echo "FrontendOptimizer.php loaded successfully<br>\n";
    
    App\FrontendOptimizer::init();
    echo "FrontendOptimizer initialized successfully<br>\n";
} catch (Exception $e) {
    echo "FrontendOptimizer failed: " . $e->getMessage() . "<br>\n";
}

echo "<h2>Test 5: View Function</h2>\n";
try {
    App\view('auth/login');
    echo "Login view rendered successfully<br>\n";
} catch (Exception $e) {
    echo "Login view failed: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>Test Complete</h2>\n";
?>
