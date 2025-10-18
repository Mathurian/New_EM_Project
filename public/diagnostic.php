<?php
// Simple diagnostic script to identify 500 error cause
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Event Manager Diagnostic</h1>\n";

echo "<h2>1. File System Check</h2>\n";
echo "Current directory: " . getcwd() . "<br>\n";
echo "PHP version: " . phpversion() . "<br>\n";

echo "<h2>2. Required Files Check</h2>\n";
$requiredFiles = [
    'app/bootstrap.php',
    'app/lib/DB.php',
    'app/lib/helpers.php',
    'app/lib/ErrorHandler.php',
    'app/lib/FrontendOptimizer.php',
    'app/lib/Cache.php',
    'app/lib/SecurityService.php',
    'app/lib/Logger.php',
    'app/routes/controllers.php',
    'app/views/auth/login.php',
    'app/views/partials/layout.php',
    'public/assets/css/style.css'
];

foreach ($requiredFiles as $file) {
    $exists = file_exists($file);
    $readable = $exists ? is_readable($file) : false;
    echo "$file: " . ($exists ? "EXISTS" : "MISSING") . 
         ($readable ? " (readable)" : ($exists ? " (not readable)" : "")) . "<br>\n";
}

echo "<h2>3. Directory Permissions Check</h2>\n";
$dirs = [
    'app',
    'app/db',
    'app/lib',
    'app/views',
    'public',
    'public/assets',
    'public/assets/css',
    'storage',
    'storage/cache',
    'storage/cache/assets'
];

foreach ($dirs as $dir) {
    $exists = is_dir($dir);
    $writable = $exists ? is_writable($dir) : false;
    echo "$dir: " . ($exists ? "EXISTS" : "MISSING") . 
         ($writable ? " (writable)" : ($exists ? " (not writable)" : "")) . "<br>\n";
}

echo "<h2>4. Database Check</h2>\n";
try {
    $dbPath = 'app/db/contest.sqlite';
    echo "Database path: $dbPath<br>\n";
    echo "Database exists: " . (file_exists($dbPath) ? "YES" : "NO") . "<br>\n";
    
    if (file_exists($dbPath)) {
        echo "Database readable: " . (is_readable($dbPath) ? "YES" : "NO") . "<br>\n";
        echo "Database writable: " . (is_writable($dbPath) ? "YES" : "NO") . "<br>\n";
        echo "Database size: " . filesize($dbPath) . " bytes<br>\n";
    }
} catch (Exception $e) {
    echo "Database check failed: " . $e->getMessage() . "<br>\n";
}

echo "<h2>5. Bootstrap Test</h2>\n";
try {
    echo "Attempting to load bootstrap...<br>\n";
    require_once 'app/bootstrap.php';
    echo "Bootstrap loaded successfully!<br>\n";
    
    echo "Testing database connection...<br>\n";
    $pdo = App\DB::pdo();
    echo "Database connection successful!<br>\n";
    
    echo "Testing basic query...<br>\n";
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . implode(', ', $tables) . "<br>\n";
    
} catch (Exception $e) {
    echo "Bootstrap test failed: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>6. Login Route Test</h2>\n";
try {
    echo "Testing login route...<br>\n";
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/login';
    $_SERVER['HTTP_HOST'] = 'localhost';
    $_SERVER['SCRIPT_NAME'] = '/public/index.php';
    
    // Simulate the login route
    $router = new App\Router();
    $router->get('/login', 'AuthController@loginForm');
    
    echo "Router created successfully!<br>\n";
    
} catch (Exception $e) {
    echo "Login route test failed: " . $e->getMessage() . "<br>\n";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h2>Diagnostic Complete</h2>\n";
?>
