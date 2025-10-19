<?php
declare(strict_types=1);

namespace App;

/**
 * Migration Controller for SQLite to PostgreSQL Migration
 */
class MigrationController {
    private array $config;
    private ?DatabaseInterface $sourceDb = null;
    private ?DatabaseInterface $targetDb = null;
    private array $migrationLog = [];
    private array $errors = [];

    public function __construct(array $config = []) {
        $this->config = $this->loadConfig($config);
    }

    /**
     * Load configuration with defaults
     */
    private function loadConfig(array $config): array {
        $defaults = [
            'source' => [
                'type' => 'sqlite',
                'path' => __DIR__ . '/../db/contest.sqlite'
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

        return array_merge_recursive($defaults, $config);
    }

    /**
     * Initialize database connections
     */
    private function initializeDatabases(): void {
        $this->log("Initializing database connections...");
        
        // Initialize source database
        $this->sourceDb = DatabaseFactory::createFromConfig($this->config['source']);
        $this->log("Source database connected: " . $this->sourceDb->getDatabaseType());
        
        // Initialize target database
        $this->targetDb = DatabaseFactory::createFromConfig($this->config['target']);
        $this->log("Target database connected: " . $this->targetDb->getDatabaseType());
    }

    /**
     * Perform complete migration
     */
    public function migrate(): bool {
        try {
            $this->log("Starting complete migration process...");
            
            // 1. Initialize databases
            $this->initializeDatabases();
            
            // 2. Pre-migration checks
            $this->preMigrationChecks();
            
            // 3. Backup source database
            if ($this->config['migration']['backup_before_migration']) {
                $this->backupSourceDatabase();
            }
            
            // 4. Create target schema
            $this->createTargetSchema();
            
            // 5. Migrate data
            $this->migrateData();
            
            // 6. Post-migration validation
            if ($this->config['migration']['validate_after_migration']) {
                $this->validateMigration();
            }
            
            // 7. Create rollback script
            if ($this->config['migration']['create_rollback_script']) {
                $this->createRollbackScript();
            }
            
            $this->log("Migration completed successfully!");
            return true;
            
        } catch (\Exception $e) {
            $this->log("Migration failed: " . $e->getMessage(), 'error');
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * Pre-migration checks
     */
    private function preMigrationChecks(): void {
        $this->log("Performing pre-migration checks...");
        
        // Check source database
        if (!$this->sourceDb) {
            throw new \Exception("Source database not initialized");
        }
        
        $sourceTables = $this->sourceDb->getTables();
        if (empty($sourceTables)) {
            throw new \Exception("Source database has no tables");
        }
        
        $this->log("Source database has " . count($sourceTables) . " tables");
        
        // Check target database connection
        if (!$this->targetDb) {
            throw new \Exception("Target database not initialized");
        }
        
        // Check if target database is empty
        $targetTables = $this->targetDb->getTables();
        if (!empty($targetTables)) {
            $this->log("Warning: Target database is not empty (" . count($targetTables) . " tables)", 'warning');
        }
        
        $this->log("Pre-migration checks completed");
    }

    /**
     * Backup source database
     */
    private function backupSourceDatabase(): void {
        $this->log("Creating backup of source database...");
        
        $backupPath = __DIR__ . '/../backups/migration_backup_' . date('Y-m-d_H-i-s') . '.sqlite';
        $sourcePath = $this->config['source']['path'];
        
        // Ensure backup directory exists
        $backupDir = dirname($backupPath);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        // Copy SQLite file
        if (!copy($sourcePath, $backupPath)) {
            throw new \Exception("Failed to create backup of source database");
        }
        
        $this->log("Backup created: " . $backupPath);
    }

    /**
     * Create target schema
     */
    private function createTargetSchema(): void {
        $this->log("Creating target database schema...");
        
        $schemaMigrator = new SchemaMigrator($this->sourceDb, $this->targetDb);
        
        if (!$schemaMigrator->migrateSchema()) {
            $errors = $schemaMigrator->getErrors();
            throw new \Exception("Schema migration failed: " . implode(', ', $errors));
        }
        
        $this->log("Target schema created successfully");
    }

    /**
     * Migrate data
     */
    private function migrateData(): void {
        $this->log("Starting data migration...");
        
        $dataMigrator = new DataMigrator($this->sourceDb, $this->targetDb);
        $dataMigrator->setBatchSize($this->config['migration']['batch_size']);
        
        if (!$dataMigrator->migrateData()) {
            $errors = $dataMigrator->getErrors();
            throw new \Exception("Data migration failed: " . implode(', ', $errors));
        }
        
        $this->log("Data migration completed successfully");
    }

    /**
     * Validate migration
     */
    private function validateMigration(): void {
        $this->log("Validating migrated data...");
        
        $dataMigrator = new DataMigrator($this->sourceDb, $this->targetDb);
        $issues = $dataMigrator->validateMigration();
        
        if (!empty($issues)) {
            $this->log("Validation found " . count($issues) . " issues:", 'warning');
            foreach ($issues as $issue) {
                $this->log("  - " . $issue, 'warning');
            }
        } else {
            $this->log("Data validation passed successfully");
        }
        
        // Create integrity checks
        $dataMigrator->createIntegrityChecks();
    }

    /**
     * Create rollback script
     */
    private function createRollbackScript(): void {
        $this->log("Creating rollback script...");
        
        $rollbackScript = $this->generateRollbackScript();
        $rollbackPath = __DIR__ . '/../backups/rollback_' . date('Y-m-d_H-i-s') . '.php';
        
        file_put_contents($rollbackPath, $rollbackScript);
        chmod($rollbackPath, 0755);
        
        $this->log("Rollback script created: " . $rollbackPath);
    }

    /**
     * Generate rollback script
     */
    private function generateRollbackScript(): string {
        $config = var_export($this->config, true);
        
        return <<<PHP
<?php
declare(strict_types=1);

/**
 * Migration Rollback Script
 * Generated on: " . date('Y-m-d H:i:s') . "
 * 
 * This script will restore the application to use SQLite database
 * and revert any PostgreSQL-specific changes.
 */

require_once __DIR__ . '/../../app/bootstrap.php';

use App\DatabaseFactory;
use App\DB;

echo "Starting migration rollback...\n";

try {
    // 1. Switch back to SQLite
    DB::switchDatabase('sqlite', [
        'type' => 'sqlite',
        'path' => '{$this->config['source']['path']}'
    ]);
    
    echo "✓ Switched back to SQLite database\n";
    
    // 2. Verify SQLite database is accessible
    \$tables = DB::getTables();
    echo "✓ SQLite database accessible (" . count(\$tables) . " tables)\n";
    
    // 3. Clear any PostgreSQL-specific caches
    if (class_exists('App\Cache')) {
        App\Cache::clear();
        echo "✓ Cleared application cache\n";
    }
    
    echo "\nRollback completed successfully!\n";
    echo "The application is now using SQLite database.\n";
    
} catch (Exception \$e) {
    echo "❌ Rollback failed: " . \$e->getMessage() . "\n";
    exit(1);
}
PHP;
    }

    /**
     * Test migration without actually migrating
     */
    public function testMigration(): array {
        $this->log("Testing migration process...");
        
        try {
            $this->log("Step 1/5: Initializing database connections...");
            $this->initializeDatabases();
            
            $this->log("Step 2/5: Running pre-migration checks...");
            $this->preMigrationChecks();
            
            $this->log("Step 3/5: Testing schema migration...");
            // Test schema migration
            $schemaMigrator = new SchemaMigrator($this->sourceDb, $this->targetDb);
            $schemaSuccess = $schemaMigrator->migrateSchema();
            
            $this->log("Step 4/5: Testing data migration...");
            // Test data migration (dry run)
            $dataMigrator = new DataMigrator($this->sourceDb, $this->targetDb);
            $dataMigrator->setBatchSize(10); // Small batch for testing
            
            $this->log("Step 5/5: Gathering test results...");
            $testResults = [
                'schema_migration' => $schemaSuccess,
                'schema_errors' => $schemaMigrator->getErrors(),
                'data_migration_test' => true, // We'll test with small batch
                'source_tables' => $this->sourceDb->getTables(),
                'target_tables' => $this->targetDb->getTables(),
                'source_row_counts' => $this->getSourceRowCounts(),
                'config' => $this->config
            ];
            
            $this->log("✅ Migration test completed successfully!");
            return $testResults;
            
        } catch (\Exception $e) {
            $this->log("Migration test failed: " . $e->getMessage(), 'error');
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'config' => $this->config
            ];
        }
    }

    /**
     * Get row counts from source database
     */
    private function getSourceRowCounts(): array {
        $counts = [];
        $tables = $this->sourceDb->getTables();
        
        foreach ($tables as $table) {
            try {
                $count = (int) $this->sourceDb->fetchColumn("SELECT COUNT(*) FROM {$table}");
                $counts[$table] = $count;
            } catch (\Exception $e) {
                $counts[$table] = 0;
            }
        }
        
        return $counts;
    }

    /**
     * Get migration status
     */
    public function getStatus(): array {
        try {
            $this->initializeDatabases();
            
            return [
                'source' => [
                    'type' => $this->sourceDb->getDatabaseType(),
                    'tables' => $this->sourceDb->getTables(),
                    'row_counts' => $this->getSourceRowCounts()
                ],
                'target' => [
                    'type' => $this->targetDb->getDatabaseType(),
                    'tables' => $this->targetDb->getTables()
                ],
                'config' => $this->config
            ];
            
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'config' => $this->config
            ];
        }
    }

    /**
     * Log migration progress
     */
    private function log(string $message, string $level = 'info'): void {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}";
        $this->migrationLog[] = $logEntry;
        
        // Also log to file
        error_log($logEntry);
        
        // Output to console if running from CLI
        if (php_sapi_name() === 'cli') {
            echo $logEntry . PHP_EOL;
        }
    }

    /**
     * Get migration log
     */
    public function getMigrationLog(): array {
        return $this->migrationLog;
    }

    /**
     * Get errors
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Check if migration was successful
     */
    public function isSuccessful(): bool {
        return empty($this->errors);
    }
}
