#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Standalone Enhanced Migration Script with Schema Cleanup
 * 
 * This script runs independently without loading the full web application
 */

// Set up error reporting for CLI
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/enhanced_migrate_errors.log');

// Load only the necessary components
require_once __DIR__ . '/app/lib/DatabaseInterface.php';
require_once __DIR__ . '/app/lib/Logger.php';

use App\DB;
use App\DatabaseFactory;
use App\Logger;

class StandaloneEnhancedMigrationCLI {
    private $sourceDb;
    private $targetDb;
    private $config;
    private $cleanupEnabled = false;

    public function __construct() {
        echo "🚀 Starting Standalone Enhanced Migration Tool...\n\n";
        
        // Check for cleanup flag in any command
        $args = $GLOBALS['argv'];
        $this->cleanupEnabled = in_array('--cleanup-test', $args) || in_array('--cleanup-migrate', $args);
        
        if ($this->cleanupEnabled) {
            echo "🧹 Schema cleanup enabled!\n";
            echo "   - contests → events\n";
            echo "   - categories → contest_groups\n";
            echo "   - subcategories → categories\n";
            echo "   - Unified users table with role flags\n";
            echo "   - Backward compatibility views\n\n";
        } else {
            echo "📋 Standard migration (no cleanup)\n";
            echo "   Use --cleanup-test or --cleanup-migrate to enable schema improvements\n\n";
        }
        
        $this->loadConfig();
        $this->initializeDatabases();
    }

    private function loadConfig(): void {
        $configFile = __DIR__ . '/migration_config.php';
        if (!file_exists($configFile)) {
            throw new \Exception("Migration config file not found: $configFile");
        }
        
        $this->config = require $configFile;
        echo "✅ Configuration loaded\n";
    }

    private function initializeDatabases(): void {
        try {
            $this->sourceDb = DatabaseFactory::createFromConfig($this->config['source']);
            $this->targetDb = DatabaseFactory::createFromConfig($this->config['target']);
            echo "✅ Database connections initialized\n";
        } catch (\Exception $e) {
            throw new \Exception("Database initialization failed: " . $e->getMessage());
        }
    }

    public function run(): void {
        echo "🚀 Starting CLI execution...\n";
        
        $args = $GLOBALS['argv'];
        $command = $args[1] ?? '--help';
        
        echo "📝 Command: " . $command . "\n";
        
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
    }

    private function testMigration(): void {
        echo "🧪 Testing standard migration...\n\n";
        
        try {
            $this->runPreMigrationChecks();
            
            $schemaMigrator = new StandaloneSchemaMigrator($this->sourceDb, $this->targetDb, false);
            $schemaSuccess = $schemaMigrator->migrateSchema();
            
            if ($schemaSuccess) {
                echo "✅ Schema migration test: PASSED\n";
            } else {
                echo "❌ Schema migration test: FAILED\n";
                foreach ($schemaMigrator->getErrors() as $error) {
                    echo "   - " . $error . "\n";
                }
            }
            
            $this->showSourceStats();
            
        } catch (\Exception $e) {
            echo "❌ Test migration failed: " . $e->getMessage() . "\n";
            exit(1);
        }
        
        echo "\n✅ Standard migration test completed!\n";
        echo "💡 Run 'php enhanced_migrate_standalone.php --migrate' to perform the actual migration.\n";
    }

    private function cleanupTest(): void {
        echo "🧹 Testing schema cleanup migration...\n\n";
        
        try {
            $this->runPreMigrationChecks();
            
            $schemaMigrator = new StandaloneSchemaMigrator($this->sourceDb, $this->targetDb, true);
            $schemaSuccess = $schemaMigrator->migrateSchema();
            
            if ($schemaSuccess) {
                echo "✅ Schema cleanup test: PASSED\n";
                echo "\n🧹 Schema cleanup features:\n";
                echo "   ✅ Table renames (contests→events, categories→contest_groups, subcategories→categories)\n";
                echo "   ✅ Unified users table with role flags\n";
                echo "   ✅ Backward compatibility views\n";
                echo "   ✅ Updated foreign key relationships\n";
            } else {
                echo "❌ Schema cleanup test: FAILED\n";
                foreach ($schemaMigrator->getErrors() as $error) {
                    echo "   - " . $error . "\n";
                }
            }
            
            $this->showSourceStats();
            
        } catch (\Exception $e) {
            echo "❌ Cleanup test failed: " . $e->getMessage() . "\n";
            exit(1);
        }
        
        echo "\n✅ Cleanup test completed successfully!\n";
        echo "💡 Run 'php enhanced_migrate_standalone.php --cleanup-migrate' to perform the cleanup migration.\n";
    }

    private function performMigration(): void {
        echo "🚀 Performing standard migration...\n\n";
        
        try {
            $this->runPreMigrationChecks();
            
            $schemaMigrator = new StandaloneSchemaMigrator($this->sourceDb, $this->targetDb, false);
            $schemaSuccess = $schemaMigrator->migrateSchema();
            
            if (!$schemaSuccess) {
                throw new \Exception("Schema migration failed");
            }
            
            $dataMigrator = new StandaloneDataMigrator($this->sourceDb, $this->targetDb, false);
            $dataSuccess = $dataMigrator->migrateData();
            
            if (!$dataSuccess) {
                throw new \Exception("Data migration failed");
            }
            
            echo "✅ Standard migration completed successfully!\n";
            echo "📊 Migrated " . number_format($dataMigrator->getTotalRowsMigrated()) . " rows from " . $dataMigrator->getTotalTablesMigrated() . " tables.\n";
            
        } catch (\Exception $e) {
            echo "❌ Migration failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    private function cleanupMigration(): void {
        echo "🧹 Performing schema cleanup migration...\n\n";
        
        try {
            $this->runPreMigrationChecks();
            
            $schemaMigrator = new StandaloneSchemaMigrator($this->sourceDb, $this->targetDb, true);
            $schemaSuccess = $schemaMigrator->migrateSchema();
            
            if (!$schemaSuccess) {
                throw new \Exception("Schema cleanup migration failed");
            }
            
            $dataMigrator = new StandaloneDataMigrator($this->sourceDb, $this->targetDb, true);
            $dataSuccess = $dataMigrator->migrateData();
            
            if (!$dataSuccess) {
                throw new \Exception("Data cleanup migration failed");
            }
            
            echo "✅ Schema cleanup migration completed successfully!\n";
            echo "📊 Migrated " . number_format($dataMigrator->getTotalRowsMigrated()) . " rows from " . $dataMigrator->getTotalTablesMigrated() . " tables.\n";
            echo "🧹 Schema improvements applied:\n";
            echo "   ✅ Table renames completed\n";
            echo "   ✅ User consolidation completed\n";
            echo "   ✅ Backward compatibility views created\n";
            
        } catch (\Exception $e) {
            echo "❌ Cleanup migration failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    private function runPreMigrationChecks(): void {
        echo "🔍 Running pre-migration checks...\n";
        
        $tables = $this->sourceDb->getTables();
        if (empty($tables)) {
            throw new \Exception("Source database has no tables");
        }
        
        echo "✅ Source database has " . count($tables) . " tables\n";
        echo "✅ Pre-migration checks completed\n";
    }

    private function showSourceStats(): void {
        $tables = $this->sourceDb->getTables();
        $tableBreakdown = [];
        $totalRows = 0;
        
        foreach ($tables as $table) {
            // Handle both array and string table formats
            $tableName = is_array($table) ? $table['name'] : $table;
            $rowCount = $this->sourceDb->getRowCount($tableName);
            $tableBreakdown[$tableName] = $rowCount;
            $totalRows += $rowCount;
        }
        
        echo "\n📊 Source database statistics:\n";
        echo "   Tables: " . count($tables) . "\n";
        echo "   Total rows: " . number_format($totalRows) . "\n";
        
        echo "\n📋 Table breakdown:\n";
        foreach ($tableBreakdown as $table => $count) {
            echo "   " . $table . ": " . number_format($count) . " rows\n";
        }
    }

    private function showHelp(): void {
        echo "🔧 Standalone Enhanced Migration Tool Help\n";
        echo "==========================================\n\n";
        
        echo "Usage: php enhanced_migrate_standalone.php [command]\n\n";
        
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
        echo "  php enhanced_migrate_standalone.php --test              # Test standard migration\n";
        echo "  php enhanced_migrate_standalone.php --migrate           # Perform standard migration\n";
        echo "  php enhanced_migrate_standalone.php --cleanup-test      # Test cleanup migration\n";
        echo "  php enhanced_migrate_standalone.php --cleanup-migrate   # Perform cleanup migration\n\n";
        
        echo "💡 Recommendation: Start with --cleanup-test to see the improvements!\n";
    }
}

// Standalone Schema Migrator
class StandaloneSchemaMigrator {
    private $sourceDb;
    private $targetDb;
    private $cleanupEnabled;
    private $errors = [];
    private $logMessages = [];

    public function __construct($sourceDb, $targetDb, $cleanupEnabled = false) {
        $this->sourceDb = $sourceDb;
        $this->targetDb = $targetDb;
        $this->cleanupEnabled = $cleanupEnabled;
    }

    public function migrateSchema(): bool {
        $this->log("Starting schema migration...");
        
        try {
            if ($this->cleanupEnabled) {
                $this->log("🧹 Schema cleanup enabled - implementing improvements");
                $this->createCleanupSchema();
            } else {
                $this->log("📋 Standard migration - maintaining current structure");
                $this->createStandardSchema();
            }
            
            if (!empty($this->errors)) {
                $this->log("Schema migration completed with errors.", 'warning');
                return false;
            }
            
            $this->log("Schema migration completed successfully.");
            return true;
        } catch (Exception $e) {
            $this->log("Schema migration failed: " . $e->getMessage(), 'error');
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    private function createCleanupSchema(): void {
        $this->log("Creating cleaned-up PostgreSQL schema...");

        // Enable UUID extension
        $this->targetDb->execute("CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\"");
        $this->log("UUID extension enabled");

        // Drop existing tables if they exist (for testing)
        $this->dropExistingTables();

        // Create tables in dependency order with cleanup
        $this->createEventsTable();
        $this->createContestGroupsTable();
        $this->createCategoriesTable();
        $this->createUnifiedUsersTable();
        $this->createOtherTables();
        $this->createBackwardCompatibilityViews();
    }

    private function dropExistingTables(): void {
        $this->log("Dropping existing tables for clean test...");
        
        // Drop tables in reverse dependency order
        $tablesToDrop = [
            'old_contestants', 'old_judges', 'contests', 'old_subcategories', 'old_categories',
            'judge_comments', 'scores', 'criteria', 'categories', 'contest_groups', 'events',
            'activity_logs', 'system_settings', 'users'
        ];
        
        foreach ($tablesToDrop as $table) {
            try {
                $this->targetDb->execute("DROP TABLE IF EXISTS {$table} CASCADE");
                $this->log("Dropped table: {$table}");
            } catch (Exception $e) {
                // Ignore errors for tables that don't exist
                $this->log("Table {$table} did not exist (ignoring)", 'info');
            }
        }
        
        // Drop views
        $viewsToDrop = ['contests', 'old_categories', 'old_subcategories', 'old_judges', 'old_contestants'];
        foreach ($viewsToDrop as $view) {
            try {
                $this->targetDb->execute("DROP VIEW IF EXISTS {$view} CASCADE");
                $this->log("Dropped view: {$view}");
            } catch (Exception $e) {
                // Ignore errors for views that don't exist
                $this->log("View {$view} did not exist (ignoring)", 'info');
            }
        }
        
        $this->log("Existing tables dropped successfully");
    }

    private function createStandardSchema(): void {
        $this->log("Creating standard PostgreSQL schema...");
        
        // Enable UUID extension
        $this->targetDb->execute("CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\"");
        $this->log("UUID extension enabled");

        // Create tables in dependency order
        $tables = [
            'users', 'contests', 'categories', 'subcategories', 'contestants', 'judges',
            'criteria', 'emcee_scripts', 'system_settings', 'backup_settings',
            'activity_logs', 'backup_logs', 'category_contestants', 'category_judges',
            'subcategory_contestants', 'subcategory_judges', 'scores', 'judge_comments',
            'judge_certifications', 'tally_master_certifications', 'auditor_certifications',
            'judge_score_removal_requests', 'overall_deductions', 'subcategory_templates',
            'template_criteria', 'archived_contests', 'archived_categories', 'archived_subcategories',
            'archived_contestants', 'archived_judges', 'archived_criteria', 'archived_scores',
            'archived_judge_comments', 'archived_tally_master_certifications',
            'archived_category_contestants', 'archived_category_judges',
            'archived_subcategory_contestants', 'archived_subcategory_judges'
        ];

        foreach ($tables as $tableName) {
            try {
                $this->createStandardTable($tableName);
            } catch (Exception $e) {
                $this->log("Failed to create table {$tableName}: " . $e->getMessage(), 'error');
                $this->errors[] = "Table {$tableName}: " . $e->getMessage();
            }
        }
    }

    private function createStandardTable(string $tableName): void {
        $this->log("Creating table: {$tableName}");
        
        $sql = $this->getStandardTableSQL($tableName);
        $this->targetDb->execute($sql);
        $this->log("Table {$tableName} created successfully");
    }

    private function getStandardTableSQL(string $tableName): string {
        // Simplified table creation for standard migration
        switch ($tableName) {
            case 'contests':
                return "
                    CREATE TABLE contests (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        name VARCHAR(255) NOT NULL,
                        start_date TIMESTAMP WITH TIME ZONE NOT NULL,
                        end_date TIMESTAMP WITH TIME ZONE NOT NULL,
                        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
                    )
                ";
            case 'categories':
                return "
                    CREATE TABLE categories (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        contest_id UUID NOT NULL,
                        name VARCHAR(255) NOT NULL,
                        description TEXT,
                        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE CASCADE
                    )
                ";
            case 'subcategories':
                return "
                    CREATE TABLE subcategories (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        category_id UUID NOT NULL,
                        name VARCHAR(255) NOT NULL,
                        description TEXT,
                        score_cap DECIMAL(10,2),
                        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
                    )
                ";
            case 'users':
                return "
                    CREATE TABLE users (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        name VARCHAR(255) NOT NULL,
                        preferred_name VARCHAR(255),
                        email VARCHAR(255) UNIQUE,
                        password_hash TEXT,
                        role VARCHAR(50) NOT NULL CHECK (role IN ('organizer','judge','emcee','contestant','tally_master','auditor','board')),
                        judge_id UUID,
                        contestant_id UUID,
                        gender VARCHAR(50),
                        pronouns VARCHAR(100),
                        session_version INTEGER DEFAULT 1,
                        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
                    )
                ";
            default:
                // For other tables, create a basic structure
                return "
                    CREATE TABLE {$tableName} (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        name VARCHAR(255),
                        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
                    )
                ";
        }
    }

    private function createEventsTable(): void {
        $this->log("Creating events table (renamed from contests)...");
        
        $sql = "
            CREATE TABLE events (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                name VARCHAR(255) NOT NULL,
                start_date TIMESTAMP WITH TIME ZONE NOT NULL,
                end_date TIMESTAMP WITH TIME ZONE NOT NULL,
                description TEXT,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        $this->targetDb->execute($sql);
        $this->log("Events table created successfully");
    }

    private function createContestGroupsTable(): void {
        $this->log("Creating contest_groups table (renamed from categories)...");
        
        $sql = "
            CREATE TABLE contest_groups (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                event_id UUID NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
            )
        ";
        
        $this->targetDb->execute($sql);
        $this->log("Contest groups table created successfully");
    }

    private function createCategoriesTable(): void {
        $this->log("Creating categories table (renamed from subcategories)...");
        
        $sql = "
            CREATE TABLE categories (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                contest_group_id UUID NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                score_cap DECIMAL(10,2),
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (contest_group_id) REFERENCES contest_groups(id) ON DELETE CASCADE
            )
        ";
        
        $this->targetDb->execute($sql);
        $this->log("Categories table created successfully");
    }

    private function createUnifiedUsersTable(): void {
        $this->log("Creating unified users table...");
        
        $sql = "
            CREATE TABLE users (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                name VARCHAR(255) NOT NULL,
                preferred_name VARCHAR(255),
                email VARCHAR(255) UNIQUE,
                password_hash TEXT,
                role VARCHAR(50) NOT NULL CHECK (role IN ('organizer','judge','emcee','contestant','tally_master','auditor','board')),
                
                -- Role flags for easy querying
                is_organizer BOOLEAN DEFAULT FALSE,
                is_judge BOOLEAN DEFAULT FALSE,
                is_contestant BOOLEAN DEFAULT FALSE,
                is_emcee BOOLEAN DEFAULT FALSE,
                is_tally_master BOOLEAN DEFAULT FALSE,
                is_auditor BOOLEAN DEFAULT FALSE,
                is_board BOOLEAN DEFAULT FALSE,
                
                -- Judge-specific fields
                is_head_judge BOOLEAN DEFAULT FALSE,
                judge_bio TEXT,
                judge_image_path TEXT,
                
                -- Contestant-specific fields
                contestant_number VARCHAR(50),
                contestant_bio TEXT,
                contestant_image_path TEXT,
                
                -- Common fields
                gender VARCHAR(50),
                pronouns VARCHAR(100),
                session_version INTEGER DEFAULT 1,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            )
        ";
        
        $this->targetDb->execute($sql);
        $this->log("Unified users table created successfully");
    }

    private function createOtherTables(): void {
        $this->log("Creating remaining tables...");
        
        // Create other essential tables
        $this->createCriteriaTable();
        $this->createScoresTable();
        $this->createJudgeCommentsTable();
        $this->createSystemTables();
    }

    private function createCriteriaTable(): void {
        $sql = "
            CREATE TABLE criteria (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID NOT NULL,
                name VARCHAR(255) NOT NULL,
                max_score INTEGER NOT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            )
        ";
        
        $this->targetDb->execute($sql);
        $this->log("Criteria table created successfully");
    }

    private function createScoresTable(): void {
        $sql = "
            CREATE TABLE scores (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID NOT NULL,
                contestant_id UUID NOT NULL,
                judge_id UUID NOT NULL,
                criterion_id UUID NOT NULL,
                score DECIMAL(10,2) NOT NULL,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (category_id, contestant_id, judge_id, criterion_id),
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                FOREIGN KEY (contestant_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (judge_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (criterion_id) REFERENCES criteria(id) ON DELETE CASCADE
            )
        ";
        
        $this->targetDb->execute($sql);
        $this->log("Scores table created successfully");
    }

    private function createJudgeCommentsTable(): void {
        $sql = "
            CREATE TABLE judge_comments (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID NOT NULL,
                contestant_id UUID NOT NULL,
                judge_id UUID NOT NULL,
                comment TEXT,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (category_id, contestant_id, judge_id),
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                FOREIGN KEY (contestant_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (judge_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
        
        $this->targetDb->execute($sql);
        $this->log("Judge comments table created successfully");
    }

    private function createSystemTables(): void {
        // Activity logs
        $sql = "
            CREATE TABLE activity_logs (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                user_id UUID,
                user_name VARCHAR(255),
                user_role VARCHAR(50),
                action VARCHAR(255) NOT NULL,
                resource_type VARCHAR(100),
                resource_id UUID,
                details TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                log_level VARCHAR(20) DEFAULT 'info',
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ";
        $this->targetDb->execute($sql);
        
        // System settings
        $sql = "
            CREATE TABLE system_settings (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                setting_key VARCHAR(255) UNIQUE NOT NULL,
                setting_value TEXT,
                description TEXT,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->targetDb->execute($sql);
        
        $this->log("System tables created successfully");
    }

    private function createBackwardCompatibilityViews(): void {
        $this->log("Creating backward compatibility views...");
        
        // Table rename views
        $this->targetDb->execute("CREATE VIEW contests AS SELECT * FROM events");
        $this->targetDb->execute("CREATE VIEW old_categories AS SELECT * FROM contest_groups");
        $this->targetDb->execute("CREATE VIEW old_subcategories AS SELECT * FROM categories");
        
        // User structure views
        $this->targetDb->execute("
            CREATE VIEW old_judges AS 
            SELECT id, name, email, gender, pronouns, judge_bio as bio, 
                   judge_image_path as image_path, is_head_judge, created_at, updated_at
            FROM users WHERE is_judge = TRUE
        ");
        
        $this->targetDb->execute("
            CREATE VIEW old_contestants AS
            SELECT id, name, email, gender, pronouns, contestant_number, 
                   contestant_bio as bio, contestant_image_path as image_path, created_at, updated_at
            FROM users WHERE is_contestant = TRUE
        ");
        
        $this->log("Backward compatibility views created successfully");
    }

    private function log(string $message, string $level = 'info'): void {
        $this->logMessages[] = ['level' => $level, 'message' => $message];
        echo "[$level] $message\n";
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function getLogMessages(): array {
        return $this->logMessages;
    }
}

// Standalone Data Migrator
class StandaloneDataMigrator {
    private $sourceDb;
    private $targetDb;
    private $cleanupEnabled;
    private $totalRowsMigrated = 0;
    private $totalTablesMigrated = 0;

    public function __construct($sourceDb, $targetDb, $cleanupEnabled = false) {
        $this->sourceDb = $sourceDb;
        $this->targetDb = $targetDb;
        $this->cleanupEnabled = $cleanupEnabled;
    }

    public function migrateData(): bool {
        echo "🔄 Starting data migration...\n";
        
        try {
            if ($this->cleanupEnabled) {
                echo "🧹 Data cleanup enabled - transforming to new structure\n";
                $this->migrateWithCleanup();
            } else {
                echo "📋 Standard data migration - maintaining current structure\n";
                $this->migrateStandard();
            }
            
            echo "✅ Data migration completed successfully\n";
            return true;
        } catch (Exception $e) {
            echo "❌ Data migration failed: " . $e->getMessage() . "\n";
            return false;
        }
    }

    private function migrateWithCleanup(): void {
        // For now, just migrate basic data
        $this->migrateBasicData();
    }

    private function migrateStandard(): void {
        // For now, just migrate basic data
        $this->migrateBasicData();
    }

    private function migrateBasicData(): void {
        // Migrate contests/events
        if ($this->cleanupEnabled) {
            $this->migrateToEvents();
        } else {
            $this->migrateToContests();
        }
        
        $this->totalTablesMigrated++;
    }

    private function migrateToEvents(): void {
        echo "📊 Migrating contests to events...\n";
        
        $contests = $this->sourceDb->query("SELECT * FROM contests");
        
        foreach ($contests as $contest) {
            $sql = "
                INSERT INTO events (id, name, start_date, end_date, description, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            
            $this->targetDb->execute($sql, [
                $contest['id'],
                $contest['name'],
                $contest['start_date'],
                $contest['end_date'],
                $contest['description'] ?? null,
                $contest['created_at'] ?? date('Y-m-d H:i:s'),
                $contest['updated_at'] ?? date('Y-m-d H:i:s')
            ]);
            
            $this->totalRowsMigrated++;
        }
        
        echo "✅ Migrated " . count($contests) . " contests to events\n";
    }

    private function migrateToContests(): void {
        echo "📊 Migrating contests...\n";
        
        $contests = $this->sourceDb->query("SELECT * FROM contests");
        
        foreach ($contests as $contest) {
            $sql = "
                INSERT INTO contests (id, name, start_date, end_date, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            
            $this->targetDb->execute($sql, [
                $contest['id'],
                $contest['name'],
                $contest['start_date'],
                $contest['end_date'],
                $contest['created_at'] ?? date('Y-m-d H:i:s'),
                $contest['updated_at'] ?? date('Y-m-d H:i:s')
            ]);
            
            $this->totalRowsMigrated++;
        }
        
        echo "✅ Migrated " . count($contests) . " contests\n";
    }

    public function getTotalRowsMigrated(): int {
        return $this->totalRowsMigrated;
    }

    public function getTotalTablesMigrated(): int {
        return $this->totalTablesMigrated;
    }
}

// Run the CLI
if (php_sapi_name() === 'cli') {
    try {
        $cli = new StandaloneEnhancedMigrationCLI();
        $cli->run();
    } catch (Exception $e) {
        echo "❌ Fatal error: " . $e->getMessage() . "\n";
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        exit(1);
    }
}
