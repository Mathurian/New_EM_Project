<?php
// MIME type diagnostic script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>MIME Type Diagnostic</h1>\n";

echo "<h2>1. Current Headers</h2>\n";
echo "Content-Type: " . (headers_sent() ? "Headers already sent" : "Not set yet") . "<br>\n";

echo "<h2>2. Server Information</h2>\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "<br>\n";
echo "Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'Unknown') . "<br>\n";

echo "<h2>3. File MIME Type Detection</h2>\n";
$files = [
    'public/assets/css/style.css',
    'public/index.php',
    'app/views/contests/new.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file);
        finfo_close($finfo);
        echo "$file: $mimeType<br>\n";
    } else {
        echo "$file: File not found<br>\n";
    }
}

echo "<h2>4. Test Content-Type Headers</h2>\n";
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
    echo "✅ Content-Type header set to: text/html; charset=UTF-8<br>\n";
} else {
    echo "❌ Headers already sent<br>\n";
}

echo "<h2>5. Test contests/new Route</h2>\n";
try {
    require_once '../app/bootstrap.php';
    
    // Simulate the contests/new route
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/contests/new';
    
    $contestController = new App\Routes\ContestController();
    ob_start();
    $contestController->new();
    $output = ob_get_clean();
    
    echo "✅ contests/new route executed successfully<br>\n";
    echo "Output length: " . strlen($output) . " characters<br>\n";
    
    // Check if output contains proper HTML
    if (strpos($output, '<!doctype html>') !== false || strpos($output, '<html') !== false) {
        echo "✅ Output contains HTML structure<br>\n";
    } else {
        echo "❌ Output may not contain proper HTML structure<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error testing contests/new route: " . $e->getMessage() . "<br>\n";
}

echo "<h2>6. Security Headers Check</h2>\n";
$securityHeaders = [
    'X-Content-Type-Options',
    'X-Frame-Options', 
    'X-XSS-Protection',
    'Content-Security-Policy'
];

foreach ($securityHeaders as $header) {
    $value = apache_get_headers()[$header] ?? 'Not set';
    echo "$header: $value<br>\n";
}

echo "<h2>Diagnostic Complete</h2>\n";
?>
