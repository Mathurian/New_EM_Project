#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * SQLite to PostgreSQL Migration CLI Tool
 * 
 * Usage:
 *   php migrate.php --help
 *   php migrate.php --test
 *   php migrate.php --migrate
 *   php migrate.php --status
 *   php migrate.php --rollback
 */

require_once __DIR__ . '/app/bootstrap.php';

use App\MigrationController;

class MigrationCLI {
    private array $config;
    private MigrationController $controller;

    public function __construct() {
        $this->config = $this->loadConfig();
        $this->controller = new MigrationController($this->config);
    }

    /**
     * Load configuration from file or environment
     */
    private function loadConfig(): array {
        $configFile = __DIR__ . '/migration_config.php';
        
        if (file_exists($configFile)) {
            $config = include $configFile;
        } else {
            $config = $this->getDefaultConfig();
        }
        
        // Override with environment variables if available
        $config['target']['host'] = $_ENV['POSTGRES_HOST'] ?? $config['target']['host'];
        $config['target']['port'] = $_ENV['POSTGRES_PORT'] ?? $config['target']['port'];
        $config['target']['dbname'] = $_ENV['POSTGRES_DB'] ?? $config['target']['dbname'];
        $config['target']['username'] = $_ENV['POSTGRES_USER'] ?? $config['target']['username'];
        $config['target']['password'] = $_ENV['POSTGRES_PASSWORD'] ?? $config['target']['password'];
        
        return $config;
    }

    /**
     * Get default configuration
     */
    private function getDefaultConfig(): array {
        return [
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

    /**
     * Main CLI entry point
     */
    public function run(array $argv): void {
        $command = $argv[1] ?? '--help';
        
        switch ($command) {
            case '--help':
            case '-h':
                $this->showHelp();
                break;
                
            case '--test':
            case '-t':
                $this->testMigration();
                break;
                
            case '--migrate':
            case '-m':
                $this->performMigration();
                break;
                
            case '--status':
            case '-s':
                $this->showStatus();
                break;
                
            case '--rollback':
            case '-r':
                $this->performRollback();
                break;
                
            case '--config':
            case '-c':
                $this->showConfig();
                break;
                
            case '--create-config':
                $this->createConfigFile();
                break;
                
            default:
                echo "Unknown command: {$command}\n";
                $this->showHelp();
                exit(1);
        }
    }

    /**
     * Show help information
     */
    private function showHelp(): void {
        echo <<<HELP
SQLite to PostgreSQL Migration Tool

USAGE:
    php migrate.php [COMMAND] [OPTIONS]

COMMANDS:
    --help, -h          Show this help message
    --test, -t          Test migration without actually migrating
    --migrate, -m       Perform the actual migration
    --status, -s        Show current migration status
    --rollback, -r      Rollback to SQLite (if rollback script exists)
    --config, -c        Show current configuration
    --create-config     Create a configuration file template

EXAMPLES:
    php migrate.php --test
    php migrate.php --migrate
    php migrate.php --status

ENVIRONMENT VARIABLES:
    POSTGRES_HOST       PostgreSQL host (default: localhost)
    POSTGRES_PORT       PostgreSQL port (default: 5432)
    POSTGRES_DB         PostgreSQL database name (default: event_manager)
    POSTGRES_USER       PostgreSQL username (default: event_manager)
    POSTGRES_PASSWORD   PostgreSQL password (default: password)

CONFIGURATION:
    Create a migration_config.php file in the project root to customize settings.

HELP;
    }

    /**
     * Test migration process
     */
    private function testMigration(): void {
        echo "🧪 Testing migration process...\n\n";
        $this->flushOutput();
        
        $results = $this->controller->testMigration();
        
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
        echo "   Tables: " . count($results['source_tables']) . "\n";
        echo "   Total rows: " . array_sum($results['source_row_counts']) . "\n";
        $this->flushOutput();
        
        echo "\n📋 Table breakdown:\n";
        foreach ($results['source_row_counts'] as $table => $count) {
            echo "   {$table}: {$count} rows\n";
            $this->flushOutput();
        }
        
        echo "\n✅ Migration test completed successfully!\n";
        echo "💡 Run 'php migrate.php --migrate' to perform the actual migration.\n";
        $this->flushOutput();
    }

    /**
     * Perform actual migration
     */
    private function performMigration(): void {
        echo "🚀 Starting migration from SQLite to PostgreSQL...\n\n";
        
        // Confirm migration
        echo "⚠️  WARNING: This will migrate your database from SQLite to PostgreSQL.\n";
        echo "   A backup will be created, but please ensure you have additional backups.\n\n";
        
        if (!$this->confirm("Do you want to continue?")) {
            echo "Migration cancelled.\n";
            exit(0);
        }
        
        $startTime = microtime(true);
        
        if ($this->controller->migrate()) {
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            echo "\n✅ Migration completed successfully!\n";
            echo "⏱️  Duration: {$duration} seconds\n";
            echo "📝 Check the migration log for details.\n";
            
            // Show next steps
            echo "\n📋 Next steps:\n";
            echo "1. Update your application configuration to use PostgreSQL\n";
            echo "2. Test your application thoroughly\n";
            echo "3. Update your deployment scripts\n";
            echo "4. Keep the rollback script for emergency use\n";
            
        } else {
            $errors = $this->controller->getErrors();
            echo "\n❌ Migration failed!\n";
            echo "Errors:\n";
            foreach ($errors as $error) {
                echo "   - " . $error . "\n";
            }
            exit(1);
        }
    }

    /**
     * Show migration status
     */
    private function showStatus(): void {
        echo "📊 Migration Status\n\n";
        
        $status = $this->controller->getStatus();
        
        if (isset($status['error'])) {
            echo "❌ Error: " . $status['error'] . "\n";
            exit(1);
        }
        
        echo "🔍 Source Database (SQLite):\n";
        echo "   Type: " . $status['source']['type'] . "\n";
        echo "   Tables: " . count($status['source']['tables']) . "\n";
        echo "   Total rows: " . array_sum($status['source']['row_counts']) . "\n";
        
        echo "\n🎯 Target Database (PostgreSQL):\n";
        echo "   Type: " . $status['target']['type'] . "\n";
        echo "   Tables: " . count($status['target']['tables']) . "\n";
        
        if (!empty($status['target']['tables'])) {
            echo "   Tables: " . implode(', ', $status['target']['tables']) . "\n";
        }
        
        echo "\n⚙️  Configuration:\n";
        echo "   Batch size: " . $status['config']['migration']['batch_size'] . "\n";
        echo "   Backup before migration: " . ($status['config']['migration']['backup_before_migration'] ? 'Yes' : 'No') . "\n";
        echo "   Validate after migration: " . ($status['config']['migration']['validate_after_migration'] ? 'Yes' : 'No') . "\n";
    }

    /**
     * Perform rollback
     */
    private function performRollback(): void {
        echo "🔄 Looking for rollback script...\n";
        
        $rollbackFiles = glob(__DIR__ . '/backups/rollback_*.php');
        
        if (empty($rollbackFiles)) {
            echo "❌ No rollback script found.\n";
            echo "   Rollback scripts are created during migration.\n";
            exit(1);
        }
        
        // Use the most recent rollback script
        $rollbackScript = end($rollbackFiles);
        
        echo "📄 Found rollback script: " . basename($rollbackScript) . "\n";
        
        if (!$this->confirm("Do you want to rollback to SQLite?")) {
            echo "Rollback cancelled.\n";
            exit(0);
        }
        
        echo "🔄 Executing rollback script...\n";
        
        $output = shell_exec("php {$rollbackScript} 2>&1");
        
        if ($output) {
            echo $output . "\n";
        }
        
        echo "✅ Rollback completed!\n";
    }

    /**
     * Show current configuration
     */
    private function showConfig(): void {
        echo "⚙️  Current Configuration\n\n";
        
        echo "Source Database:\n";
        echo "   Type: " . $this->config['source']['type'] . "\n";
        echo "   Path: " . $this->config['source']['path'] . "\n";
        
        echo "\nTarget Database:\n";
        echo "   Type: " . $this->config['target']['type'] . "\n";
        echo "   Host: " . $this->config['target']['host'] . "\n";
        echo "   Port: " . $this->config['target']['port'] . "\n";
        echo "   Database: " . $this->config['target']['dbname'] . "\n";
        echo "   Username: " . $this->config['target']['username'] . "\n";
        
        echo "\nMigration Settings:\n";
        echo "   Batch size: " . $this->config['migration']['batch_size'] . "\n";
        echo "   Backup before migration: " . ($this->config['migration']['backup_before_migration'] ? 'Yes' : 'No') . "\n";
        echo "   Validate after migration: " . ($this->config['migration']['validate_after_migration'] ? 'Yes' : 'No') . "\n";
        echo "   Create rollback script: " . ($this->config['migration']['create_rollback_script'] ? 'Yes' : 'No') . "\n";
    }

    /**
     * Create configuration file template
     */
    private function createConfigFile(): void {
        $configFile = __DIR__ . '/migration_config.php';
        
        if (file_exists($configFile)) {
            if (!$this->confirm("Configuration file already exists. Overwrite?")) {
                echo "Configuration file creation cancelled.\n";
                exit(0);
            }
        }
        
        $configContent = <<<PHP
<?php
declare(strict_types=1);

/**
 * Migration Configuration
 * 
 * This file contains the configuration for migrating from SQLite to PostgreSQL.
 * Modify the values below to match your environment.
 */

return [
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
PHP;
        
        file_put_contents($configFile, $configContent);
        
        echo "✅ Configuration file created: migration_config.php\n";
        echo "📝 Please edit the file to match your environment before running migration.\n";
    }

    /**
     * Confirm user action
     */
    private function confirm(string $message): bool {
        echo "{$message} (y/N): ";
        $response = trim(fgets(STDIN));
        return strtolower($response) === 'y' || strtolower($response) === 'yes';
    }

    /**
     * Flush output to ensure real-time display
     */
    private function flushOutput(): void {
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
}

// Run CLI if called directly
if (php_sapi_name() === 'cli') {
    $cli = new MigrationCLI();
    $cli->run($argv);
}
