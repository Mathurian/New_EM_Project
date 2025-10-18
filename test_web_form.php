<?php
/**
 * Web Form Submission Test Script
 * Tests the actual web form submission process
 */

require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/SecurityService.php';
require_once __DIR__ . '/app/lib/helpers.php';

// Import functions from App namespace
use function App\{csrf_field, sanitize_input, url, redirect, uuid};

echo "Web Form Submission Test\n";
echo "=======================\n\n";

// Test CSRF token generation
echo "1. Testing CSRF token generation...\n";
try {
    
    $csrfToken = csrf_field();
    if (!empty($csrfToken)) {
        echo "✅ CSRF token generated successfully\n";
        echo "Token preview: " . substr($csrfToken, 0, 50) . "...\n";
    } else {
        echo "❌ CSRF token generation failed\n";
    }
} catch (Exception $e) {
    echo "❌ CSRF token generation error: " . $e->getMessage() . "\n";
}

// Test form data sanitization
echo "\n2. Testing form data sanitization...\n";
try {
    $rawData = [
        'name' => 'Test User<script>alert("xss")</script>',
        'email' => 'test@example.com',
        'password' => 'TestPass123!',
        'role' => 'tally_master',
        'preferred_name' => 'Test',
        'gender' => 'other',
        'pronouns' => 'they/them'
    ];
    
    $sanitizedData = sanitize_input($rawData);
    
    echo "Raw data: " . json_encode($rawData) . "\n";
    echo "Sanitized data: " . json_encode($sanitizedData) . "\n";
    
    if ($sanitizedData['name'] !== $rawData['name']) {
        echo "✅ Sanitization working (XSS removed)\n";
    } else {
        echo "❌ Sanitization may not be working\n";
    }
    
} catch (Exception $e) {
    echo "❌ Sanitization error: " . $e->getMessage() . "\n";
}

// Test URL generation
echo "\n3. Testing URL generation...\n";
try {
    $userUrl = url('users');
    echo "Users URL: $userUrl\n";
    
    if (!empty($userUrl)) {
        echo "✅ URL generation working\n";
    } else {
        echo "❌ URL generation failed\n";
    }
} catch (Exception $e) {
    echo "❌ URL generation error: " . $e->getMessage() . "\n";
}

// Test session handling
echo "\n4. Testing session handling...\n";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['test'] = 'test_value';
    if (isset($_SESSION['test']) && $_SESSION['test'] === 'test_value') {
        echo "✅ Session handling working\n";
        unset($_SESSION['test']);
    } else {
        echo "❌ Session handling failed\n";
    }
} catch (Exception $e) {
    echo "❌ Session handling error: " . $e->getMessage() . "\n";
}

// Test redirect function
echo "\n5. Testing redirect function...\n";
try {
    // We can't actually redirect in a test script, but we can test if the function exists
    if (function_exists('App\redirect')) {
        echo "✅ Redirect function exists\n";
    } else {
        echo "❌ Redirect function not found\n";
    }
} catch (Exception $e) {
    echo "❌ Redirect function error: " . $e->getMessage() . "\n";
}

// Test UUID generation
echo "\n6. Testing UUID generation...\n";
try {
    $testUuid = uuid();
    if (!empty($testUuid) && strlen($testUuid) > 10) {
        echo "✅ UUID generation working: $testUuid\n";
    } else {
        echo "❌ UUID generation failed\n";
    }
} catch (Exception $e) {
    echo "❌ UUID generation error: " . $e->getMessage() . "\n";
}

echo "\n🎉 Web form test completed!\n";
echo "If all tests passed, the issue might be in the actual form submission or controller routing.\n";
