#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Enhanced Schema Migrator with Cleanup Options
 * 
 * This migrator includes schema cleanup and consolidation features
 */

require_once __DIR__ . '/app/bootstrap.php';

use App\DB;
use App\DatabaseFactory;
use App\Logger;

class EnhancedSchemaMigrator {
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
        $this->log("Starting enhanced schema migration...");
        
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

        // Create tables in dependency order with cleanup
        $this->createEventsTable();
        $this->createContestGroupsTable();
        $this->createCategoriesTable();
        $this->createUnifiedUsersTable();
        $this->createOtherTables();
        $this->createBackwardCompatibilityViews();
    }

    private function createStandardSchema(): void {
        $this->log("Creating standard PostgreSQL schema...");
        
        // Enable UUID extension
        $this->targetDb->execute("CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\"");
        $this->log("UUID extension enabled");

        // Use existing SchemaMigrator logic
        $migrator = new \App\SchemaMigrator($this->sourceDb, $this->targetDb);
        $migrator->migrateSchema();
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
        
        // Create other tables (criteria, scores, etc.) with updated foreign keys
        $this->createCriteriaTable();
        $this->createScoresTable();
        $this->createJudgeCommentsTable();
        $this->createCertificationTables();
        $this->createSystemTables();
        $this->createArchivedTables();
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

    private function createCertificationTables(): void {
        // Tally master certifications
        $sql = "
            CREATE TABLE tally_master_certifications (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID NOT NULL,
                signature_name VARCHAR(255) NOT NULL,
                certified_at TIMESTAMP WITH TIME ZONE NOT NULL,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            )
        ";
        $this->targetDb->execute($sql);
        
        // Auditor certifications
        $sql = "
            CREATE TABLE auditor_certifications (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID NOT NULL,
                signature_name VARCHAR(255) NOT NULL,
                certified_at TIMESTAMP WITH TIME ZONE NOT NULL,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
            )
        ";
        $this->targetDb->execute($sql);
        
        // Judge certifications
        $sql = "
            CREATE TABLE judge_certifications (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                category_id UUID NOT NULL,
                judge_id UUID NOT NULL,
                signature_name VARCHAR(255) NOT NULL,
                certified_at TIMESTAMP WITH TIME ZONE NOT NULL,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                FOREIGN KEY (judge_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
        $this->targetDb->execute($sql);
        
        $this->log("Certification tables created successfully");
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
        
        // Backup settings
        $sql = "
            CREATE TABLE backup_settings (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                backup_type VARCHAR(50) NOT NULL CHECK (backup_type IN ('schema', 'full')),
                enabled BOOLEAN DEFAULT FALSE,
                frequency VARCHAR(20) NOT NULL CHECK (frequency IN ('minutes', 'hours', 'daily', 'weekly', 'monthly')),
                frequency_value INTEGER DEFAULT 1,
                retention_days INTEGER DEFAULT 30,
                last_run TIMESTAMP WITH TIME ZONE,
                next_run TIMESTAMP WITH TIME ZONE,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->targetDb->execute($sql);
        
        // Backup logs
        $sql = "
            CREATE TABLE backup_logs (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                backup_type VARCHAR(50) NOT NULL CHECK (backup_type IN ('schema', 'full', 'scheduled')),
                file_path TEXT NOT NULL,
                file_size BIGINT NOT NULL,
                status VARCHAR(20) NOT NULL CHECK (status IN ('success', 'failed', 'in_progress')),
                created_by UUID,
                created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                error_message TEXT,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
            )
        ";
        $this->targetDb->execute($sql);
        
        $this->log("System tables created successfully");
    }

    private function createArchivedTables(): void {
        // Archived events
        $sql = "
            CREATE TABLE archived_events (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                name VARCHAR(255) NOT NULL,
                description TEXT,
                start_date TIMESTAMP WITH TIME ZONE,
                end_date TIMESTAMP WITH TIME ZONE,
                archived_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                archived_by UUID NOT NULL,
                FOREIGN KEY (archived_by) REFERENCES users(id) ON DELETE CASCADE
            )
        ";
        $this->targetDb->execute($sql);
        
        // Archived contest groups
        $sql = "
            CREATE TABLE archived_contest_groups (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                archived_event_id UUID NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                FOREIGN KEY (archived_event_id) REFERENCES archived_events(id) ON DELETE CASCADE
            )
        ";
        $this->targetDb->execute($sql);
        
        // Archived categories
        $sql = "
            CREATE TABLE archived_categories (
                id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                archived_contest_group_id UUID NOT NULL,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                score_cap DECIMAL(10,2),
                FOREIGN KEY (archived_contest_group_id) REFERENCES archived_contest_groups(id) ON DELETE CASCADE
            )
        ";
        $this->targetDb->execute($sql);
        
        $this->log("Archived tables created successfully");
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
        Logger::log($message, $level);
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function getLogMessages(): array {
        return $this->logMessages;
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    echo "🧹 Enhanced Schema Migrator with Cleanup Options\n";
    echo "================================================\n\n";
    
    $cleanupEnabled = in_array('--cleanup', $argv);
    
    if ($cleanupEnabled) {
        echo "✅ Schema cleanup enabled\n";
        echo "   - contests → events\n";
        echo "   - categories → contest_groups\n";
        echo "   - subcategories → categories\n";
        echo "   - Unified users table with role flags\n";
        echo "   - Backward compatibility views\n\n";
    } else {
        echo "📋 Standard migration (no cleanup)\n";
        echo "   Use --cleanup flag to enable schema improvements\n\n";
    }
    
    echo "Usage: php enhanced_schema_migrator.php [--cleanup]\n";
}
