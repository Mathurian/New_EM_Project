#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Quick Migration Status Checker
 * 
 * Usage: php check_migration_status.php
 */

require_once __DIR__ . '/app/bootstrap.php';

use App\MigrationController;

echo "🔍 Quick Migration Status Check\n";
echo "===============================\n\n";

try {
    // Load configuration
    $configFile = __DIR__ . '/migration_config.php';
    if (!file_exists($configFile)) {
        echo "❌ Migration configuration not found.\n";
        echo "💡 Run: php migrate.php --create-config\n";
        exit(1);
    }
    
    $config = include $configFile;
    echo "✅ Configuration loaded\n";
    
    // Test source database
    echo "🔍 Checking SQLite database...\n";
    $sourceDb = \App\DatabaseFactory::createSQLite($config['source']['path']);
    $sourceTables = $sourceDb->getTables();
    echo "   📊 SQLite tables: " . count($sourceTables) . "\n";
    
    if (empty($sourceTables)) {
        echo "❌ No tables found in SQLite database\n";
        exit(1);
    }
    
    // Test PostgreSQL connection
    echo "🔍 Checking PostgreSQL connection...\n";
    try {
        $targetDb = \App\DatabaseFactory::createFromConfig($config['target']);
        $targetTables = $targetDb->getTables();
        echo "   📊 PostgreSQL tables: " . count($targetTables) . "\n";
        echo "   ✅ PostgreSQL connection successful\n";
    } catch (\Exception $e) {
        echo "   ❌ PostgreSQL connection failed: " . $e->getMessage() . "\n";
        echo "   💡 Check your PostgreSQL configuration and credentials\n";
        exit(1);
    }
    
    // Check if migration is in progress
    echo "🔍 Checking for running migration...\n";
    $processes = shell_exec('ps aux | grep "migrate.php" | grep -v grep');
    if (!empty($processes)) {
        echo "   ⏳ Migration is currently running\n";
        echo "   📝 Process info:\n";
        echo "   " . trim($processes) . "\n";
    } else {
        echo "   ✅ No migration currently running\n";
    }
    
    // Check log files
    echo "🔍 Checking log files...\n";
    $logFile = __DIR__ . '/logs/event-manager.log';
    if (file_exists($logFile)) {
        $logSize = filesize($logFile);
        $logModified = date('Y-m-d H:i:s', filemtime($logFile));
        echo "   📄 Log file size: " . number_format($logSize) . " bytes\n";
        echo "   🕒 Last modified: {$logModified}\n";
        
        // Show last few lines
        $lastLines = shell_exec("tail -5 {$logFile}");
        if (!empty($lastLines)) {
            echo "   📋 Recent log entries:\n";
            $lines = explode("\n", trim($lastLines));
            foreach ($lines as $line) {
                if (!empty($line)) {
                    echo "      {$line}\n";
                }
            }
        }
    } else {
        echo "   ⚠️  No log file found\n";
    }
    
    echo "\n✅ Status check completed!\n";
    echo "\n💡 Next steps:\n";
    echo "   - If migration is running: php monitor_migration.php\n";
    echo "   - If migration failed: Check logs and run php migrate.php --test\n";
    echo "   - If ready to migrate: php migrate.php --migrate\n";
    
} catch (\Exception $e) {
    echo "❌ Status check failed: " . $e->getMessage() . "\n";
    exit(1);
}
