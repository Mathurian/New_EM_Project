<?php
/**
 * URL Generation Test Script
 * Tests URL generation and form submission routing
 */

require_once __DIR__ . '/app/lib/helpers.php';

// Import functions from App namespace
use function App\{url, csrf_field};

echo "URL Generation Test\n";
echo "===================\n\n";

// Test URL generation
echo "1. Testing URL generation...\n";
$usersUrl = url('users');
echo "url('users') generates: $usersUrl\n";

// Test CSRF field generation
echo "\n2. Testing CSRF field generation...\n";
$csrfField = csrf_field();
echo "CSRF field: " . substr($csrfField, 0, 100) . "...\n";

// Test form action URL
echo "\n3. Testing form action URL...\n";
echo "Form should POST to: $usersUrl\n";

// Check if this matches the expected route
echo "\n4. Route verification...\n";
echo "Expected route: POST /users -> UserController@create\n";
echo "Generated URL: $usersUrl\n";

if (strpos($usersUrl, '/users') !== false) {
    echo "✅ URL generation looks correct\n";
} else {
    echo "❌ URL generation issue detected\n";
}

echo "\n5. Testing URL with different paths...\n";
echo "url('') generates: " . url('') . "\n";
echo "url('/') generates: " . url('/') . "\n";
echo "url('users/new') generates: " . url('users/new') . "\n";

echo "\n6. Server environment check...\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT SET') . "\n";

echo "\n🎯 URL generation test completed!\n";
