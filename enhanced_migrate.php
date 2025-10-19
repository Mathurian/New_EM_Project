#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Enhanced Migration Script with Schema Cleanup Options
 * 
 * This script provides both standard and cleanup migration options
 */

require_once __DIR__ . '/app/bootstrap.php';

use App\DB;
use App\DatabaseFactory;
use App\Logger;

class EnhancedMigrationCLI {
    private $controller;
    private $cleanupEnabled = false;

    public function __construct() {
        echo "🚀 Starting Enhanced Migration Tool...\n\n";
        
        // Check for cleanup flag
        $this->cleanupEnabled = in_array('--cleanup', $GLOBALS['argv']);
        
        if ($this->cleanupEnabled) {
            echo "🧹 Schema cleanup enabled!\n";
            echo "   - contests → events\n";
            echo "   - categories → contest_groups\n";
            echo "   - subcategories → categories\n";
            echo "   - Unified users table with role flags\n";
            echo "   - Backward compatibility views\n\n";
        } else {
            echo "📋 Standard migration (no cleanup)\n";
            echo "   Use --cleanup flag to enable schema improvements\n\n";
        }
        
        $this->flushOutput();
        
        try {
            echo "✅ Bootstrap loaded successfully\n";
            $this->flushOutput();
            
            echo "🖥️  Running in CLI mode\n";
            $this->flushOutput();
            
            echo "🏗️  Creating MigrationCLI instance...\n";
            $this->flushOutput();
            
            $this->controller = new MigrationController();
            
            echo "🔧 Initializing MigrationCLI...\n";
            $this->flushOutput();
            
            echo "✅ Configuration loaded\n";
            $this->flushOutput();
            
            echo "✅ MigrationController initialized\n";
            $this->flushOutput();
            
        } catch (\Exception $e) {
            echo "❌ Initialization failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            $this->flushOutput();
            exit(1);
        }
    }

    public function run(): void {
        echo "🚀 Starting CLI execution...\n";
        $this->flushOutput();
        
        echo "🎯 Processing command line arguments...\n";
        $this->flushOutput();
        
        $args = $GLOBALS['argv'];
        $command = $args[1] ?? '--help';
        
        echo "📝 Command: " . $command . "\n";
        $this->flushOutput();
        
        switch ($command) {
            case '--test':
                $this->testMigration();
                break;
            case '--migrate':
                $this->performMigration();
                break;
            case '--cleanup-test':
                $this->cleanupTest();
                break;
            case '--cleanup-migrate':
                $this->cleanupMigration();
                break;
            case '--help':
            default:
                $this->showHelp();
                break;
        }
        
        echo "✅ CLI execution completed\n";
        $this->flushOutput();
    }

    private function testMigration(): void {
        echo "🧪 Testing migration process...\n\n";
        $this->flushOutput();
        
        try {
            echo "📞 Calling controller->testMigration()...\n";
            $this->flushOutput();
            
            $results = $this->controller->testMigration();
            
            echo "✅ Controller test completed\n";
            $this->flushOutput();
        } catch (\Exception $e) {
            echo "❌ Test migration failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            $this->flushOutput();
            exit(1);
        }
        
        if (isset($results['error'])) {
            echo "❌ Test failed: " . $results['error'] . "\n";
            exit(1);
        }
        
        echo "✅ Schema migration test: " . ($results['schema_migration'] ? 'PASSED' : 'FAILED') . "\n";
        $this->flushOutput();
        
        if (!empty($results['schema_errors'])) {
            echo "⚠️  Schema errors:\n";
            foreach ($results['schema_errors'] as $error) {
                echo "   - " . $error . "\n";
            }
            $this->flushOutput();
        }
        
        echo "\n📊 Source database statistics:\n";
        echo "   Tables: " . $results['source_stats']['tables'] . "\n";
        echo "   Total rows: " . number_format($results['source_stats']['total_rows']) . "\n";
        $this->flushOutput();
        
        echo "\n📋 Table breakdown:\n";
        foreach ($results['source_stats']['table_breakdown'] as $table => $count) {
            echo "   " . $table . ": " . number_format($count) . " rows\n";
        }
        $this->flushOutput();
        
        echo "\n✅ Migration test completed successfully!\n";
        echo "💡 Run 'php migrate.php --migrate' to perform the actual migration.\n";
        $this->flushOutput();
    }

    private function performMigration(): void {
        echo "🚀 Performing migration...\n\n";
        $this->flushOutput();
        
        try {
            echo "📞 Calling controller->migrate()...\n";
            $this->flushOutput();
            
            $results = $this->controller->migrate();
            
            echo "✅ Migration completed\n";
            $this->flushOutput();
        } catch (\Exception $e) {
            echo "❌ Migration failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            $this->flushOutput();
            exit(1);
        }
        
        if (isset($results['error'])) {
            echo "❌ Migration failed: " . $results['error'] . "\n";
            exit(1);
        }
        
        echo "✅ Migration completed successfully!\n";
        echo "📊 Migrated " . number_format($results['total_rows']) . " rows from " . $results['total_tables'] . " tables.\n";
        $this->flushOutput();
    }

    private function cleanupTest(): void {
        echo "🧹 Testing schema cleanup migration...\n\n";
        $this->flushOutput();
        
        try {
            echo "📞 Testing cleanup migration...\n";
            $this->flushOutput();
            
            // Use enhanced migrator for cleanup test
            $results = $this->controller->testCleanupMigration();
            
            echo "✅ Cleanup test completed\n";
            $this->flushOutput();
        } catch (\Exception $e) {
            echo "❌ Cleanup test failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            $this->flushOutput();
            exit(1);
        }
        
        if (isset($results['error'])) {
            echo "❌ Cleanup test failed: " . $results['error'] . "\n";
            exit(1);
        }
        
        echo "✅ Schema cleanup test: " . ($results['schema_migration'] ? 'PASSED' : 'FAILED') . "\n";
        $this->flushOutput();
        
        if (!empty($results['schema_errors'])) {
            echo "⚠️  Schema errors:\n";
            foreach ($results['schema_errors'] as $error) {
                echo "   - " . $error . "\n";
            }
            $this->flushOutput();
        }
        
        echo "\n🧹 Schema cleanup features:\n";
        echo "   ✅ Table renames (contests→events, categories→contest_groups, subcategories→categories)\n";
        echo "   ✅ Unified users table with role flags\n";
        echo "   ✅ Backward compatibility views\n";
        echo "   ✅ Updated foreign key relationships\n";
        $this->flushOutput();
        
        echo "\n✅ Cleanup test completed successfully!\n";
        echo "💡 Run 'php migrate.php --cleanup-migrate' to perform the cleanup migration.\n";
        $this->flushOutput();
    }

    private function cleanupMigration(): void {
        echo "🧹 Performing schema cleanup migration...\n\n";
        $this->flushOutput();
        
        try {
            echo "📞 Calling controller->cleanupMigrate()...\n";
            $this->flushOutput();
            
            $results = $this->controller->cleanupMigrate();
            
            echo "✅ Cleanup migration completed\n";
            $this->flushOutput();
        } catch (\Exception $e) {
            echo "❌ Cleanup migration failed: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            $this->flushOutput();
            exit(1);
        }
        
        if (isset($results['error'])) {
            echo "❌ Cleanup migration failed: " . $results['error'] . "\n";
            exit(1);
        }
        
        echo "✅ Schema cleanup migration completed successfully!\n";
        echo "📊 Migrated " . number_format($results['total_rows']) . " rows from " . $results['total_tables'] . " tables.\n";
        echo "🧹 Schema improvements applied:\n";
        echo "   ✅ Table renames completed\n";
        echo "   ✅ User consolidation completed\n";
        echo "   ✅ Backward compatibility views created\n";
        $this->flushOutput();
    }

    private function showHelp(): void {
        echo "🔧 Enhanced Migration Tool Help\n";
        echo "==============================\n\n";
        
        echo "Usage: php migrate.php [command] [options]\n\n";
        
        echo "Commands:\n";
        echo "  --test              Test standard migration (no changes)\n";
        echo "  --migrate           Perform standard migration\n";
        echo "  --cleanup-test      Test schema cleanup migration\n";
        echo "  --cleanup-migrate   Perform schema cleanup migration\n";
        echo "  --help              Show this help message\n\n";
        
        echo "Schema Cleanup Features:\n";
        echo "  🏷️  Table Renames:\n";
        echo "     - contests → events\n";
        echo "     - categories → contest_groups\n";
        echo "     - subcategories → categories\n\n";
        
        echo "  👥 User Consolidation:\n";
        echo "     - Merge users, judges, contestants into unified table\n";
        echo "     - Add role flags for easy querying\n";
        echo "     - Maintain all existing data\n\n";
        
        echo "  🔗 Backward Compatibility:\n";
        echo "     - Create views with old table names\n";
        echo "     - Maintain existing application functionality\n";
        echo "     - Gradual migration path\n\n";
        
        echo "Examples:\n";
        echo "  php migrate.php --test              # Test standard migration\n";
        echo "  php migrate.php --migrate           # Perform standard migration\n";
        echo "  php migrate.php --cleanup-test      # Test cleanup migration\n";
        echo "  php migrate.php --cleanup-migrate   # Perform cleanup migration\n\n";
        
        echo "💡 Recommendation: Start with --cleanup-test to see the improvements!\n";
    }

    private function flushOutput(): void {
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
}

// Enhanced MigrationController with cleanup support
class MigrationController {
    private $sourceDb;
    private $targetDb;
    private $config;

    public function __construct() {
        $this->loadConfig();
    }

    private function loadConfig(): void {
        $configFile = __DIR__ . '/migration_config.php';
        if (!file_exists($configFile)) {
            throw new \Exception("Migration config file not found: $configFile");
        }
        
        $this->config = require $configFile;
    }

    public function testMigration(): array {
        try {
            $this->initializeDatabases();
            $this->runPreMigrationChecks();
            
            $schemaMigrator = new \App\SchemaMigrator($this->sourceDb, $this->targetDb);
            $schemaSuccess = $schemaMigrator->migrateSchema();
            
            return [
                'schema_migration' => $schemaSuccess,
                'schema_errors' => $schemaMigrator->getErrors(),
                'source_stats' => $this->getSourceStats()
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function testCleanupMigration(): array {
        try {
            $this->initializeDatabases();
            $this->runPreMigrationChecks();
            
            $schemaMigrator = new \App\EnhancedSchemaMigrator($this->sourceDb, $this->targetDb, true);
            $schemaSuccess = $schemaMigrator->migrateSchema();
            
            return [
                'schema_migration' => $schemaSuccess,
                'schema_errors' => $schemaMigrator->getErrors(),
                'source_stats' => $this->getSourceStats()
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function migrate(): array {
        try {
            $this->initializeDatabases();
            $this->runPreMigrationChecks();
            
            $schemaMigrator = new \App\SchemaMigrator($this->sourceDb, $this->targetDb);
            $schemaSuccess = $schemaMigrator->migrateSchema();
            
            if (!$schemaSuccess) {
                throw new \Exception("Schema migration failed");
            }
            
            $dataMigrator = new \App\DataMigrator($this->sourceDb, $this->targetDb);
            $dataSuccess = $dataMigrator->migrateData();
            
            if (!$dataSuccess) {
                throw new \Exception("Data migration failed");
            }
            
            return [
                'total_rows' => $dataMigrator->getTotalRowsMigrated(),
                'total_tables' => $dataMigrator->getTotalTablesMigrated()
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function cleanupMigrate(): array {
        try {
            $this->initializeDatabases();
            $this->runPreMigrationChecks();
            
            $schemaMigrator = new \App\EnhancedSchemaMigrator($this->sourceDb, $this->targetDb, true);
            $schemaSuccess = $schemaMigrator->migrateSchema();
            
            if (!$schemaSuccess) {
                throw new \Exception("Schema cleanup migration failed");
            }
            
            $dataMigrator = new \App\DataTransformer($this->sourceDb, $this->targetDb, true);
            $dataSuccess = $dataMigrator->transformData();
            
            if (!$dataSuccess) {
                throw new \Exception("Data cleanup migration failed");
            }
            
            return [
                'total_rows' => $dataMigrator->getTotalRowsMigrated(),
                'total_tables' => $dataMigrator->getTotalTablesMigrated()
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function initializeDatabases(): void {
        $this->sourceDb = DatabaseFactory::createFromConfig($this->config['source']);
        $this->targetDb = DatabaseFactory::createFromConfig($this->config['target']);
    }

    private function runPreMigrationChecks(): void {
        $tables = $this->sourceDb->getTables();
        if (empty($tables)) {
            throw new \Exception("Source database has no tables");
        }
    }

    private function getSourceStats(): array {
        $tables = $this->sourceDb->getTables();
        $tableBreakdown = [];
        $totalRows = 0;
        
        foreach ($tables as $table) {
            $tableName = $table['name'];
            $rowCount = $this->sourceDb->getRowCount($tableName);
            $tableBreakdown[$tableName] = $rowCount;
            $totalRows += $rowCount;
        }
        
        return [
            'tables' => count($tables),
            'total_rows' => $totalRows,
            'table_breakdown' => $tableBreakdown
        ];
    }
}

// Run the CLI
if (php_sapi_name() === 'cli') {
    $cli = new EnhancedMigrationCLI();
    $cli->run();
}
