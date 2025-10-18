<?php
/**
 * URL Generation Fix Test Script
 * Tests the fixed URL generation function
 */

require_once __DIR__ . '/app/lib/helpers.php';

// Import functions from App namespace
use function App\{url};

echo "URL Generation Fix Test\n";
echo "======================\n\n";

// Test 1: URL generation with missing HTTP_HOST
echo "1. Testing URL generation with missing HTTP_HOST...\n";
$usersUrl = url('users');
echo "url('users') = $usersUrl\n";

// Test 2: Different URL paths
echo "\n2. Testing different URL paths...\n";
echo "url('') = " . url('') . "\n";
echo "url('/') = " . url('/') . "\n";
echo "url('users/new') = " . url('users/new') . "\n";
echo "url('admin/users') = " . url('admin/users') . "\n";

// Test 3: Server environment check
echo "\n3. Server environment check...\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET (using fallback)') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'NOT SET') . "\n";

// Test 4: Check if URL looks valid
echo "\n4. URL validation check...\n";
if (strpos($usersUrl, 'http://') === 0 && strpos($usersUrl, '/users') !== false) {
    echo "✅ URL generation looks correct\n";
    echo "✅ Form should now submit to: $usersUrl\n";
} else {
    echo "❌ URL generation still has issues\n";
}

echo "\n🎯 URL generation fix test completed!\n";
echo "If the URL looks correct, the form submission should now work.\n";
