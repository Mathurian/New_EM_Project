#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Minimal Migration Test (No Bootstrap)
 * 
 * This script tests migration components without loading the full bootstrap
 */

// Enable comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🧪 Minimal Migration Test (No Bootstrap)\n";
echo "========================================\n\n";

// Test 1: Check if files exist
echo "1. Checking file existence...\n";

$requiredFiles = [
    'app/lib/helpers.php',
    'app/lib/Config.php',
    'app/lib/DatabaseInterface.php',
    'app/lib/SchemaMigrator.php',
    'app/lib/DataMigrator.php',
    'app/lib/MigrationController.php',
    'app/lib/DB.php',
    'app/db/contest.sqlite'
];

foreach ($requiredFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "   ✅ {$file}\n";
    } else {
        echo "   ❌ {$file} - MISSING\n";
    }
}

// Test 2: Check SQLite database
echo "\n2. Testing SQLite database...\n";
$dbPath = __DIR__ . '/app/db/contest.sqlite';
if (file_exists($dbPath)) {
    echo "   ✅ Database file exists\n";
    
    // Test basic SQLite connection
    try {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        echo "   ✅ Database connection successful\n";
        echo "   📊 Tables found: " . count($tables) . "\n";
        
        foreach ($tables as $table) {
            $count = $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            echo "      - {$table}: {$count} rows\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Database connection failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ❌ Database file not found\n";
}

// Test 3: Check PostgreSQL connection
echo "\n3. Testing PostgreSQL connection...\n";
try {
    $pdo = new PDO('pgsql:host=localhost;port=5432;dbname=event_manager', 'event_manager', 'password');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'")->fetchAll(PDO::FETCH_COLUMN);
    echo "   ✅ PostgreSQL connection successful\n";
    echo "   📊 Tables found: " . count($tables) . "\n";
    
} catch (Exception $e) {
    echo "   ❌ PostgreSQL connection failed: " . $e->getMessage() . "\n";
    echo "   💡 Make sure PostgreSQL is running and credentials are correct\n";
}

// Test 4: Check PHP extensions
echo "\n4. Checking PHP extensions...\n";
$extensions = ['pdo', 'pdo_sqlite', 'pdo_pgsql'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ {$ext}\n";
    } else {
        echo "   ❌ {$ext} - NOT LOADED\n";
    }
}

// Test 5: Check configuration file
echo "\n5. Checking configuration...\n";
$configFile = __DIR__ . '/migration_config.php';
if (file_exists($configFile)) {
    echo "   ✅ Configuration file exists\n";
    try {
        $config = include $configFile;
        echo "   📊 Source type: " . $config['source']['type'] . "\n";
        echo "   📊 Target type: " . $config['target']['type'] . "\n";
    } catch (Exception $e) {
        echo "   ❌ Configuration file error: " . $e->getMessage() . "\n";
    }
} else {
    echo "   ⚠️  Configuration file not found\n";
    echo "   💡 Run: php migrate.php --create-config\n";
}

echo "\n🎉 Minimal test completed!\n";
echo "\n💡 Next steps:\n";
echo "   - If all tests passed: Run debug_bootstrap.php\n";
echo "   - If PostgreSQL failed: Check PostgreSQL setup\n";
echo "   - If files missing: Check file uploads\n";
