#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Configuration Debug Script
 * 
 * This script tests the configuration loading to find the array issue
 */

require_once __DIR__ . '/app/bootstrap.php';

use App\MigrationController;

echo "🔍 Configuration Debug Script\n";
echo "==============================\n\n";

// Test 1: Test MigrationCLI configuration loading
echo "1. Testing MigrationCLI configuration...\n";

// Simulate the MigrationCLI configuration loading
$configFile = __DIR__ . '/migration_config.php';

if (file_exists($configFile)) {
    echo "   ✅ Migration config file exists\n";
    $config = include $configFile;
} else {
    echo "   ⚠️  Using default configuration\n";
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
        ],
        'migration' => [
            'batch_size' => 1000,
            'backup_before_migration' => true,
            'validate_after_migration' => true,
            'create_rollback_script' => true
        ]
    ];
}

// Override with environment variables
$config['target']['host'] = $_ENV['POSTGRES_HOST'] ?? $config['target']['host'];
$config['target']['port'] = $_ENV['POSTGRES_PORT'] ?? $config['target']['port'];
$config['target']['dbname'] = $_ENV['POSTGRES_DB'] ?? $config['target']['dbname'];
$config['target']['username'] = $_ENV['POSTGRES_USER'] ?? $config['target']['username'];
$config['target']['password'] = $_ENV['POSTGRES_PASSWORD'] ?? $config['target']['password'];

echo "   📊 Final configuration:\n";
echo "   Source type: " . gettype($config['source']['type']) . " = " . var_export($config['source']['type'], true) . "\n";
echo "   Target type: " . gettype($config['target']['type']) . " = " . var_export($config['target']['type'], true) . "\n";

// Test 2: Test MigrationController configuration loading
echo "\n2. Testing MigrationController configuration...\n";

try {
    $controller = new MigrationController($config);
    echo "   ✅ MigrationController created successfully\n";
} catch (\Exception $e) {
    echo "   ❌ MigrationController creation failed: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Test 3: Test DatabaseFactory directly
echo "\n3. Testing DatabaseFactory directly...\n";

try {
    echo "   Testing source database creation...\n";
    $sourceDb = \App\DatabaseFactory::createFromConfig($config['source']);
    echo "   ✅ Source database created: " . $sourceDb->getDatabaseType() . "\n";
} catch (\Exception $e) {
    echo "   ❌ Source database creation failed: " . $e->getMessage() . "\n";
}

try {
    echo "   Testing target database creation...\n";
    $targetDb = \App\DatabaseFactory::createFromConfig($config['target']);
    echo "   ✅ Target database created: " . $targetDb->getDatabaseType() . "\n";
} catch (\Exception $e) {
    echo "   ❌ Target database creation failed: " . $e->getMessage() . "\n";
}

echo "\n🎉 Configuration debug completed!\n";
