#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Simple Migration Test Script
 * 
 * This script tests the basic components without the full migration system
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🧪 Simple Migration Test\n";
echo "=======================\n\n";

// Test 1: Check if we can load the bootstrap
echo "1. Testing bootstrap loading...\n";
try {
    require_once __DIR__ . '/app/bootstrap.php';
    echo "   ✅ Bootstrap loaded successfully\n";
} catch (\Exception $e) {
    echo "   ❌ Bootstrap failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check if we can create database connections
echo "2. Testing database connections...\n";

// Test SQLite connection
echo "   Testing SQLite connection...\n";
try {
    $sqliteDb = \App\DatabaseFactory::createSQLite(__DIR__ . '/app/db/contest.sqlite');
    $tables = $sqliteDb->getTables();
    echo "   ✅ SQLite connected - " . count($tables) . " tables found\n";
} catch (\Exception $e) {
    echo "   ❌ SQLite connection failed: " . $e->getMessage() . "\n";
}

// Test PostgreSQL connection
echo "   Testing PostgreSQL connection...\n";
try {
    $pgConfig = [
        'type' => 'postgresql',
        'host' => 'localhost',
        'port' => '5432',
        'dbname' => 'event_manager',
        'username' => 'event_manager',
        'password' => 'password'
    ];
    
    $pgDb = \App\DatabaseFactory::createFromConfig($pgConfig);
    $tables = $pgDb->getTables();
    echo "   ✅ PostgreSQL connected - " . count($tables) . " tables found\n";
} catch (\Exception $e) {
    echo "   ❌ PostgreSQL connection failed: " . $e->getMessage() . "\n";
    echo "   💡 Make sure PostgreSQL is running and credentials are correct\n";
}

// Test 3: Check configuration
echo "3. Testing configuration...\n";
try {
    $configFile = __DIR__ . '/migration_config.php';
    if (file_exists($configFile)) {
        $config = include $configFile;
        echo "   ✅ Configuration file found and loaded\n";
        echo "   📊 Source type: " . $config['source']['type'] . "\n";
        echo "   📊 Target type: " . $config['target']['type'] . "\n";
    } else {
        echo "   ⚠️  Configuration file not found\n";
        echo "   💡 Run: php migrate.php --create-config\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Configuration test failed: " . $e->getMessage() . "\n";
}

// Test 4: Check if MigrationController can be created
echo "4. Testing MigrationController creation...\n";
try {
    $config = [
        'source' => [
            'type' => 'sqlite',
            'path' => __DIR__ . '/app/db/contest.sqlite'
        ],
        'target' => [
            'type' => 'postgresql',
            'host' => 'localhost',
            'port' => '5432',
            'dbname' => 'event_manager',
            'username' => 'event_manager',
            'password' => 'password'
        ]
    ];
    
    $controller = new \App\MigrationController($config);
    echo "   ✅ MigrationController created successfully\n";
} catch (\Exception $e) {
    echo "   ❌ MigrationController creation failed: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n🎉 Simple test completed!\n";
echo "\n💡 Next steps:\n";
echo "   - If all tests passed: php migrate.php --test\n";
echo "   - If PostgreSQL failed: Check your PostgreSQL setup\n";
echo "   - If config missing: php migrate.php --create-config\n";
