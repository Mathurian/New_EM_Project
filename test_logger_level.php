<?php
declare(strict_types=1);

echo "=== Logger Level Test ===\n";

// Load bootstrap
require_once __DIR__ . '/app/bootstrap.php';

echo "1. Testing Logger initialization...\n";
try {
    \App\Logger::initialize();
    echo "✅ Logger initialized\n";
} catch (Exception $e) {
    echo "❌ Logger initialization failed: " . $e->getMessage() . "\n";
}

echo "2. Checking current log level...\n";
try {
    $currentLevel = \App\Logger::getLevel();
    echo "Current log level: $currentLevel\n";
} catch (Exception $e) {
    echo "❌ Failed to get log level: " . $e->getMessage() . "\n";
}

echo "3. Setting log level to debug...\n";
try {
    \App\Logger::setLevel('debug');
    echo "✅ Log level set to debug\n";
} catch (Exception $e) {
    echo "❌ Failed to set log level: " . $e->getMessage() . "\n";
}

echo "4. Verifying log level...\n";
try {
    $newLevel = \App\Logger::getLevel();
    echo "New log level: $newLevel\n";
} catch (Exception $e) {
    echo "❌ Failed to get new log level: " . $e->getMessage() . "\n";
}

echo "5. Testing debug logging...\n";
try {
    \App\Logger::debug('test_debug', 'test', 'test_id', 'This is a test debug message');
    echo "✅ Debug log test completed\n";
} catch (Exception $e) {
    echo "❌ Debug log test failed: " . $e->getMessage() . "\n";
}

echo "6. Testing info logging...\n";
try {
    \App\Logger::info('test_info', 'test', 'test_id', 'This is a test info message');
    echo "✅ Info log test completed\n";
} catch (Exception $e) {
    echo "❌ Info log test failed: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
