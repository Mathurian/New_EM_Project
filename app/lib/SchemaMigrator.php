<?php
declare(strict_types=1);

namespace App;

/**
 * Schema Migration System for SQLite to PostgreSQL
 */
class SchemaMigrator {
    private DatabaseInterface $sourceDb;
    private DatabaseInterface $targetDb;
    private array $migrationLog = [];
    private array $errors = [];

    public function __construct(DatabaseInterface $sourceDb, DatabaseInterface $targetDb) {
        $this->sourceDb = $sourceDb;
        $this->targetDb = $targetDb;
    }

    /**
     * Migrate entire schema from SQLite to PostgreSQL
     */
    public function migrateSchema(): bool {
        try {
            $this->log("Starting schema migration...");
            
            // 1. Create PostgreSQL schema
            $this->createPostgreSQLSchema();
            
            // 2. Create indexes
            $this->createIndexes();
            
            // 3. Create constraints
            $this->createConstraints();
            
            // 4. Verify schema
            $this->verifySchema();
            
            $this->log("Schema migration completed successfully!");
            return true;
            
        } catch (\Exception $e) {
            $this->log("Schema migration failed: " . $e->getMessage(), 'error');
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * Create PostgreSQL-compatible schema
     */
    private function createPostgreSQLSchema(): void {
        $this->log("Creating PostgreSQL schema...");
        
        // Enable UUID extension
        try {
            $this->targetDb->execute("CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\"");
            $this->log("UUID extension enabled");
        } catch (\Exception $e) {
            $this->log("UUID extension warning: " . $e->getMessage(), 'warning');
        }
        
        // Create tables in dependency order
        $tables = $this->getTableCreationOrder();
        
        foreach ($tables as $tableName) {
            $this->createTable($tableName);
        }
    }

    /**
     * Get tables in correct creation order (respecting foreign keys)
     */
    private function getTableCreationOrder(): array {
        return [
            'contests',
            'categories', 
            'subcategories',
            'contestants',
            'judges',
            'users',
            'subcategory_contestants',
            'subcategory_judges',
            'category_contestants',
            'category_judges',
            'criteria',
            'scores',
            'judge_comments',
            'tally_master_certifications',
            'subcategory_templates',
            'template_criteria',
            'archived_contests',
            'archived_categories',
            'archived_subcategories',
            'archived_contestants',
            'archived_judges',
            'archived_criteria',
            'archived_scores',
            'archived_judge_comments',
            'archived_tally_master_certifications',
            'activity_logs',
            'overall_deductions',
            'system_settings',
            'backup_logs',
            'backup_settings',
            'emcee_scripts',
            'auditor_certifications',
            'judge_score_removal_requests'
        ];
    }

    /**
     * Create individual table with PostgreSQL-compatible schema
     */
    private function createTable(string $tableName): void {
        $this->log("Creating table: {$tableName}");
        
        try {
            $sql = $this->getPostgreSQLTableSQL($tableName);
            $this->targetDb->execute($sql);
            $this->log("Table {$tableName} created successfully");
        } catch (\Exception $e) {
            $this->log("Failed to create table {$tableName}: " . $e->getMessage(), 'error');
            $this->errors[] = "Table {$tableName}: " . $e->getMessage();
            throw $e;
        }
    }

    /**
     * Get PostgreSQL-compatible SQL for each table
     */
    private function getPostgreSQLTableSQL(string $tableName): string {
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
                
            case 'contestants':
                return "
                    CREATE TABLE contestants (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255),
                        gender VARCHAR(50),
                        pronouns VARCHAR(100),
                        contestant_number INTEGER,
                        bio TEXT,
                        image_path TEXT,
                        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
                    )
                ";
                
            case 'judges':
                return "
                    CREATE TABLE judges (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255),
                        gender VARCHAR(50),
                        pronouns VARCHAR(100),
                        bio TEXT,
                        image_path TEXT,
                        is_head_judge BOOLEAN NOT NULL DEFAULT FALSE,
                        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
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
                        session_version INTEGER NOT NULL DEFAULT 1,
                        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE SET NULL,
                        FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE SET NULL
                    )
                ";
                
            case 'scores':
                return "
                    CREATE TABLE scores (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        subcategory_id UUID NOT NULL,
                        contestant_id UUID NOT NULL,
                        judge_id UUID NOT NULL,
                        criterion_id UUID NOT NULL,
                        score DECIMAL(10,2) NOT NULL,
                        created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE (subcategory_id, contestant_id, judge_id, criterion_id),
                        FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
                        FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
                        FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE,
                        FOREIGN KEY (criterion_id) REFERENCES criteria(id) ON DELETE CASCADE
                    )
                ";
                
            case 'criteria':
                return "
                    CREATE TABLE criteria (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        subcategory_id UUID NOT NULL,
                        name VARCHAR(255) NOT NULL,
                        max_score INTEGER NOT NULL,
                        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE
                    )
                ";
                
            case 'activity_logs':
                return "
                    CREATE TABLE activity_logs (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        user_id UUID,
                        user_name VARCHAR(255),
                        user_role VARCHAR(50),
                        action VARCHAR(255) NOT NULL,
                        resource_type VARCHAR(100),
                        resource_id UUID,
                        details TEXT,
                        ip_address INET,
                        user_agent TEXT,
                        log_level VARCHAR(20) NOT NULL DEFAULT 'info',
                        created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                    )
                ";
                
            case 'system_settings':
                return "
                    CREATE TABLE system_settings (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        setting_key VARCHAR(255) UNIQUE NOT NULL,
                        setting_value TEXT NOT NULL,
                        description TEXT,
                        updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_by UUID,
                        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
                    )
                ";
                
            case 'backup_logs':
                return "
                    CREATE TABLE backup_logs (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        backup_type VARCHAR(50) NOT NULL CHECK (backup_type IN ('schema', 'full', 'scheduled')),
                        file_path TEXT NOT NULL,
                        file_size BIGINT NOT NULL,
                        status VARCHAR(20) NOT NULL CHECK (status IN ('success', 'failed', 'in_progress')),
                        created_by UUID,
                        created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        error_message TEXT,
                        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
                    )
                ";
                
            case 'backup_settings':
                return "
                    CREATE TABLE backup_settings (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        backup_type VARCHAR(50) NOT NULL CHECK (backup_type IN ('schema', 'full')),
                        enabled BOOLEAN NOT NULL DEFAULT FALSE,
                        frequency VARCHAR(20) NOT NULL CHECK (frequency IN ('minutes', 'hours', 'daily', 'weekly', 'monthly')),
                        frequency_value INTEGER NOT NULL DEFAULT 1,
                        retention_days INTEGER NOT NULL DEFAULT 30,
                        last_run TIMESTAMP WITH TIME ZONE,
                        next_run TIMESTAMP WITH TIME ZONE,
                        created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
                    )
                ";
                
            case 'emcee_scripts':
                return "
                    CREATE TABLE emcee_scripts (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        filename VARCHAR(255) NOT NULL,
                        file_path TEXT NOT NULL,
                        is_active BOOLEAN DEFAULT TRUE,
                        created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
                    )
                ";
                
            case 'auditor_certifications':
                return "
                    CREATE TABLE auditor_certifications (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        subcategory_id UUID NOT NULL,
                        signature_name VARCHAR(255) NOT NULL,
                        certified_at TIMESTAMP WITH TIME ZONE NOT NULL,
                        FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE
                    )
                ";
                
            case 'judge_score_removal_requests':
                return "
                    CREATE TABLE judge_score_removal_requests (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        subcategory_id UUID NOT NULL,
                        contestant_id UUID NOT NULL,
                        judge_id UUID NOT NULL,
                        reason TEXT NOT NULL,
                        requested_by UUID NOT NULL,
                        requested_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        status VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
                        approved_by UUID,
                        approved_at TIMESTAMP WITH TIME ZONE,
                        FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
                        FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
                        FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE,
                        FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
                        FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
                    )
                ";
                
            // Junction tables
            case 'subcategory_contestants':
                return "
                    CREATE TABLE subcategory_contestants (
                        subcategory_id UUID NOT NULL,
                        contestant_id UUID NOT NULL,
                        PRIMARY KEY (subcategory_id, contestant_id),
                        FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
                        FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE
                    )
                ";
                
            case 'subcategory_judges':
                return "
                    CREATE TABLE subcategory_judges (
                        subcategory_id UUID NOT NULL,
                        judge_id UUID NOT NULL,
                        PRIMARY KEY (subcategory_id, judge_id),
                        FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
                        FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE
                    )
                ";
                
            case 'category_contestants':
                return "
                    CREATE TABLE category_contestants (
                        category_id UUID NOT NULL,
                        contestant_id UUID NOT NULL,
                        PRIMARY KEY (category_id, contestant_id),
                        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                        FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE
                    )
                ";
                
            case 'category_judges':
                return "
                    CREATE TABLE category_judges (
                        category_id UUID NOT NULL,
                        judge_id UUID NOT NULL,
                        PRIMARY KEY (category_id, judge_id),
                        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
                        FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE
                    )
                ";
                
            case 'judge_comments':
                return "
                    CREATE TABLE judge_comments (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        subcategory_id UUID NOT NULL,
                        contestant_id UUID NOT NULL,
                        judge_id UUID NOT NULL,
                        comment TEXT,
                        created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        UNIQUE (subcategory_id, contestant_id, judge_id),
                        FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
                        FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
                        FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE
                    )
                ";
                
            case 'tally_master_certifications':
                return "
                    CREATE TABLE tally_master_certifications (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        subcategory_id UUID NOT NULL,
                        signature_name VARCHAR(255) NOT NULL,
                        certified_at TIMESTAMP WITH TIME ZONE NOT NULL,
                        FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE
                    )
                ";
                
            case 'subcategory_templates':
                return "
                    CREATE TABLE subcategory_templates (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        name VARCHAR(255) NOT NULL,
                        description TEXT,
                        subcategory_names TEXT,
                        max_score INTEGER DEFAULT 60,
                        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
                    )
                ";
                
            case 'template_criteria':
                return "
                    CREATE TABLE template_criteria (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        template_id UUID NOT NULL,
                        name VARCHAR(255) NOT NULL,
                        max_score INTEGER NOT NULL,
                        created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (template_id) REFERENCES subcategory_templates(id) ON DELETE CASCADE
                    )
                ";
                
            case 'overall_deductions':
                return "
                    CREATE TABLE overall_deductions (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        subcategory_id UUID NOT NULL,
                        contestant_id UUID NOT NULL,
                        amount DECIMAL(10,2) NOT NULL,
                        comment TEXT,
                        signature_name VARCHAR(255),
                        signed_at TIMESTAMP WITH TIME ZONE,
                        created_by UUID,
                        created_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
                        FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
                        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
                    )
                ";
                
            // Archived tables (similar structure but for archived data)
            case 'archived_contests':
                return "
                    CREATE TABLE archived_contests (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        name VARCHAR(255) NOT NULL,
                        description TEXT,
                        start_date TIMESTAMP WITH TIME ZONE,
                        end_date TIMESTAMP WITH TIME ZONE,
                        archived_at TIMESTAMP WITH TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        archived_by VARCHAR(255) NOT NULL
                    )
                ";
                
            case 'archived_categories':
                return "
                    CREATE TABLE archived_categories (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        archived_contest_id UUID NOT NULL,
                        name VARCHAR(255) NOT NULL,
                        description TEXT,
                        FOREIGN KEY (archived_contest_id) REFERENCES archived_contests(id) ON DELETE CASCADE
                    )
                ";
                
            case 'archived_subcategories':
                return "
                    CREATE TABLE archived_subcategories (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        archived_category_id UUID NOT NULL,
                        name VARCHAR(255) NOT NULL,
                        description TEXT,
                        score_cap DECIMAL(10,2),
                        FOREIGN KEY (archived_category_id) REFERENCES archived_categories(id) ON DELETE CASCADE
                    )
                ";
                
            case 'archived_contestants':
                return "
                    CREATE TABLE archived_contestants (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255),
                        gender VARCHAR(50),
                        contestant_number INTEGER,
                        bio TEXT,
                        image_path TEXT
                    )
                ";
                
            case 'archived_judges':
                return "
                    CREATE TABLE archived_judges (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        name VARCHAR(255) NOT NULL,
                        email VARCHAR(255),
                        gender VARCHAR(50),
                        bio TEXT,
                        image_path TEXT
                    )
                ";
                
            case 'archived_criteria':
                return "
                    CREATE TABLE archived_criteria (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        archived_subcategory_id UUID NOT NULL,
                        name VARCHAR(255) NOT NULL,
                        max_score INTEGER NOT NULL,
                        FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE
                    )
                ";
                
            case 'archived_scores':
                return "
                    CREATE TABLE archived_scores (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        archived_subcategory_id UUID NOT NULL,
                        archived_contestant_id UUID NOT NULL,
                        archived_judge_id UUID NOT NULL,
                        archived_criterion_id UUID NOT NULL,
                        score DECIMAL(10,2) NOT NULL,
                        created_at TIMESTAMP WITH TIME ZONE NOT NULL,
                        FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE,
                        FOREIGN KEY (archived_contestant_id) REFERENCES archived_contestants(id) ON DELETE CASCADE,
                        FOREIGN KEY (archived_judge_id) REFERENCES archived_judges(id) ON DELETE CASCADE,
                        FOREIGN KEY (archived_criterion_id) REFERENCES archived_criteria(id) ON DELETE CASCADE
                    )
                ";
                
            case 'archived_judge_comments':
                return "
                    CREATE TABLE archived_judge_comments (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        archived_subcategory_id UUID NOT NULL,
                        archived_contestant_id UUID NOT NULL,
                        archived_judge_id UUID NOT NULL,
                        comment TEXT,
                        created_at TIMESTAMP WITH TIME ZONE NOT NULL,
                        FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE,
                        FOREIGN KEY (archived_contestant_id) REFERENCES archived_contestants(id) ON DELETE CASCADE,
                        FOREIGN KEY (archived_judge_id) REFERENCES archived_judges(id) ON DELETE CASCADE
                    )
                ";
                
            case 'archived_tally_master_certifications':
                return "
                    CREATE TABLE archived_tally_master_certifications (
                        id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                        archived_subcategory_id UUID NOT NULL,
                        signature_name VARCHAR(255) NOT NULL,
                        certified_at TIMESTAMP WITH TIME ZONE NOT NULL,
                        FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE
                    )
                ";
                
            default:
                throw new \InvalidArgumentException("Unknown table: {$tableName}");
        }
    }

    /**
     * Create indexes for better performance
     */
    private function createIndexes(): void {
        $this->log("Creating indexes...");
        
        $indexes = [
            // Primary indexes for foreign keys
            "CREATE INDEX CONCURRENTLY idx_categories_contest_id ON categories(contest_id)",
            "CREATE INDEX CONCURRENTLY idx_subcategories_category_id ON subcategories(category_id)",
            "CREATE INDEX CONCURRENTLY idx_users_judge_id ON users(judge_id)",
            "CREATE INDEX CONCURRENTLY idx_users_contestant_id ON users(contestant_id)",
            "CREATE INDEX CONCURRENTLY idx_scores_subcategory_id ON scores(subcategory_id)",
            "CREATE INDEX CONCURRENTLY idx_scores_contestant_id ON scores(contestant_id)",
            "CREATE INDEX CONCURRENTLY idx_scores_judge_id ON scores(judge_id)",
            "CREATE INDEX CONCURRENTLY idx_scores_criterion_id ON scores(criterion_id)",
            "CREATE INDEX CONCURRENTLY idx_criteria_subcategory_id ON criteria(subcategory_id)",
            "CREATE INDEX CONCURRENTLY idx_judge_comments_subcategory_id ON judge_comments(subcategory_id)",
            "CREATE INDEX CONCURRENTLY idx_judge_comments_contestant_id ON judge_comments(contestant_id)",
            "CREATE INDEX CONCURRENTLY idx_judge_comments_judge_id ON judge_comments(judge_id)",
            "CREATE INDEX CONCURRENTLY idx_tally_master_certifications_subcategory_id ON tally_master_certifications(subcategory_id)",
            "CREATE INDEX CONCURRENTLY idx_activity_logs_user_id ON activity_logs(user_id)",
            "CREATE INDEX CONCURRENTLY idx_activity_logs_created_at ON activity_logs(created_at)",
            "CREATE INDEX CONCURRENTLY idx_overall_deductions_subcategory_id ON overall_deductions(subcategory_id)",
            "CREATE INDEX CONCURRENTLY idx_overall_deductions_contestant_id ON overall_deductions(contestant_id)",
            
            // Performance indexes
            "CREATE INDEX CONCURRENTLY idx_users_email ON users(email)",
            "CREATE INDEX CONCURRENTLY idx_users_role ON users(role)",
            "CREATE INDEX CONCURRENTLY idx_contestants_contestant_number ON contestants(contestant_number)",
            "CREATE INDEX CONCURRENTLY idx_judges_is_head_judge ON judges(is_head_judge)",
            "CREATE INDEX CONCURRENTLY idx_scores_created_at ON scores(created_at)",
            "CREATE INDEX CONCURRENTLY idx_system_settings_setting_key ON system_settings(setting_key)",
            "CREATE INDEX CONCURRENTLY idx_backup_logs_created_at ON backup_logs(created_at)",
            "CREATE INDEX CONCURRENTLY idx_backup_logs_status ON backup_logs(status)",
        ];
        
        foreach ($indexes as $indexSql) {
            try {
                $this->targetDb->execute($indexSql);
                $this->log("Index created: " . substr($indexSql, 0, 50) . "...");
            } catch (\Exception $e) {
                $this->log("Failed to create index: " . $e->getMessage(), 'warning');
            }
        }
    }

    /**
     * Create additional constraints
     */
    private function createConstraints(): void {
        $this->log("Creating additional constraints...");
        
        // Add check constraints for data validation
        $constraints = [
            "ALTER TABLE scores ADD CONSTRAINT chk_score_positive CHECK (score >= 0)",
            "ALTER TABLE scores ADD CONSTRAINT chk_score_max CHECK (score <= 100)",
            "ALTER TABLE criteria ADD CONSTRAINT chk_max_score_positive CHECK (max_score > 0)",
            "ALTER TABLE contestants ADD CONSTRAINT chk_contestant_number_positive CHECK (contestant_number > 0)",
            "ALTER TABLE overall_deductions ADD CONSTRAINT chk_deduction_amount_positive CHECK (amount >= 0)",
            "ALTER TABLE backup_settings ADD CONSTRAINT chk_frequency_value_positive CHECK (frequency_value > 0)",
            "ALTER TABLE backup_settings ADD CONSTRAINT chk_retention_days_positive CHECK (retention_days > 0)",
        ];
        
        foreach ($constraints as $constraintSql) {
            try {
                $this->targetDb->execute($constraintSql);
                $this->log("Constraint created: " . substr($constraintSql, 0, 50) . "...");
            } catch (\Exception $e) {
                $this->log("Failed to create constraint: " . $e->getMessage(), 'warning');
            }
        }
    }

    /**
     * Verify schema integrity
     */
    private function verifySchema(): void {
        $this->log("Verifying schema integrity...");
        
        $sourceTables = $this->sourceDb->getTables();
        $targetTables = $this->targetDb->getTables();
        
        // Check if all tables exist
        foreach ($sourceTables as $table) {
            if (!in_array($table, $targetTables)) {
                throw new \Exception("Table {$table} missing in target database");
            }
        }
        
        $this->log("Schema verification completed successfully");
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
