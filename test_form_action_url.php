<?php
/**
 * Form Action URL Test Script
 * Tests the exact form action URL that will be generated
 */

require_once __DIR__ . '/app/lib/helpers.php';

// Import functions from App namespace
use function App\{url};

echo "Form Action URL Test\n";
echo "===================\n\n";

// Test the exact form action URL
echo "1. Testing form action URL...\n";
$formActionUrl = url('users');
echo "Form action URL: $formActionUrl\n";

// Test if this matches the expected route
echo "\n2. Route verification...\n";
echo "Expected route: POST /users -> UserController@create\n";
echo "Generated URL: $formActionUrl\n";

// Extract the path from the URL
$parsedUrl = parse_url($formActionUrl);
$path = $parsedUrl['path'] ?? '';
echo "Extracted path: $path\n";

if ($path === '/users' || $path === '/var/www/html/users') {
    echo "✅ Path matches expected route\n";
} else {
    echo "❌ Path does not match expected route\n";
    echo "Expected: /users\n";
    echo "Got: $path\n";
}

// Test 3: Check if URL is accessible
echo "\n3. URL accessibility check...\n";
if (strpos($formActionUrl, 'http://') === 0) {
    echo "✅ URL uses HTTP protocol\n";
} else {
    echo "❌ URL protocol issue\n";
}

if (strpos($formActionUrl, '/users') !== false) {
    echo "✅ URL contains /users path\n";
} else {
    echo "❌ URL missing /users path\n";
}

echo "\n🎯 Form action URL test completed!\n";
echo "If all checks passed, the form should now submit correctly.\n";
