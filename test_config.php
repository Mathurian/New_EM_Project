#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Config Test Script
 * 
 * This script tests the Config class specifically
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/config_test.log');

echo "🧪 Config Test Script\n";
echo "====================\n\n";

// Test 1: Load Config class
echo "1. Loading Config class...\n";
try {
    require_once __DIR__ . '/app/lib/Config.php';
    echo "   ✅ Config class loaded\n";
} catch (\Exception $e) {
    echo "   ❌ Config class failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Test Config::init()
echo "2. Testing Config::init()...\n";
try {
    echo "   Calling Config::init()...\n";
    \App\Config::init();
    echo "   ✅ Config::init() completed successfully\n";
} catch (\Exception $e) {
    echo "   ❌ Config::init() failed: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

// Test 3: Test Config::get()
echo "3. Testing Config::get()...\n";
try {
    $env = \App\Config::get('app.env', 'production');
    echo "   ✅ Config::get() works - Environment: {$env}\n";
    
    $debug = \App\Config::get('app.debug', false);
    echo "   ✅ Config::get() works - Debug: " . ($debug ? 'true' : 'false') . "\n";
} catch (\Exception $e) {
    echo "   ❌ Config::get() failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Test database config
echo "4. Testing database configuration...\n";
try {
    $dbConfig = \App\Config::getDatabaseConfig();
    echo "   ✅ Database config retrieved\n";
    echo "   📊 Database type: " . $dbConfig['type'] . "\n";
} catch (\Exception $e) {
    echo "   ❌ Database config failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Config test completed successfully!\n";
echo "\n💡 The Config class is now working properly.\n";
echo "   You can now run: php debug_bootstrap.php\n";
