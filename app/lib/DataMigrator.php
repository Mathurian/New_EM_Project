<?php
declare(strict_types=1);

namespace App;

/**
 * Data Migration System for SQLite to PostgreSQL
 */
class DataMigrator {
    private DatabaseInterface $sourceDb;
    private DatabaseInterface $targetDb;
    private array $migrationLog = [];
    private array $errors = [];
    private int $batchSize = 1000;
    private array $dataTypeMappings = [];

    public function __construct(DatabaseInterface $sourceDb, DatabaseInterface $targetDb) {
        $this->sourceDb = $sourceDb;
        $this->targetDb = $targetDb;
        $this->initializeDataTypeMappings();
    }

    /**
     * Initialize data type mappings for conversion
     */
    private function initializeDataTypeMappings(): void {
        $this->dataTypeMappings = [
            'contests' => [
                'id' => 'uuid',
                'name' => 'string',
                'start_date' => 'timestamp',
                'end_date' => 'timestamp'
            ],
            'categories' => [
                'id' => 'uuid',
                'contest_id' => 'uuid',
                'name' => 'string',
                'description' => 'string'
            ],
            'subcategories' => [
                'id' => 'uuid',
                'category_id' => 'uuid',
                'name' => 'string',
                'description' => 'string',
                'score_cap' => 'decimal'
            ],
            'contestants' => [
                'id' => 'uuid',
                'name' => 'string',
                'email' => 'string',
                'gender' => 'string',
                'pronouns' => 'string',
                'contestant_number' => 'integer',
                'bio' => 'string',
                'image_path' => 'string'
            ],
            'judges' => [
                'id' => 'uuid',
                'name' => 'string',
                'email' => 'string',
                'gender' => 'string',
                'pronouns' => 'string',
                'bio' => 'string',
                'image_path' => 'string',
                'is_head_judge' => 'boolean'
            ],
            'users' => [
                'id' => 'uuid',
                'name' => 'string',
                'preferred_name' => 'string',
                'email' => 'string',
                'password_hash' => 'string',
                'role' => 'string',
                'judge_id' => 'uuid',
                'contestant_id' => 'uuid',
                'gender' => 'string',
                'pronouns' => 'string',
                'session_version' => 'integer'
            ],
            'scores' => [
                'id' => 'uuid',
                'subcategory_id' => 'uuid',
                'contestant_id' => 'uuid',
                'judge_id' => 'uuid',
                'criterion_id' => 'uuid',
                'score' => 'decimal',
                'created_at' => 'timestamp'
            ],
            'criteria' => [
                'id' => 'uuid',
                'subcategory_id' => 'uuid',
                'name' => 'string',
                'max_score' => 'integer'
            ],
            'activity_logs' => [
                'id' => 'uuid',
                'user_id' => 'uuid',
                'user_name' => 'string',
                'user_role' => 'string',
                'action' => 'string',
                'resource_type' => 'string',
                'resource_id' => 'uuid',
                'details' => 'string',
                'ip_address' => 'string',
                'user_agent' => 'string',
                'log_level' => 'string',
                'created_at' => 'timestamp'
            ],
            'system_settings' => [
                'id' => 'uuid',
                'setting_key' => 'string',
                'setting_value' => 'string',
                'description' => 'string',
                'updated_at' => 'timestamp',
                'updated_by' => 'uuid'
            ],
            'backup_logs' => [
                'id' => 'uuid',
                'backup_type' => 'string',
                'file_path' => 'string',
                'file_size' => 'integer',
                'status' => 'string',
                'created_by' => 'uuid',
                'created_at' => 'timestamp',
                'error_message' => 'string'
            ],
            'backup_settings' => [
                'id' => 'uuid',
                'backup_type' => 'string',
                'enabled' => 'boolean',
                'frequency' => 'string',
                'frequency_value' => 'integer',
                'retention_days' => 'integer',
                'last_run' => 'timestamp',
                'next_run' => 'timestamp',
                'created_at' => 'timestamp',
                'updated_at' => 'timestamp'
            ],
            'emcee_scripts' => [
                'id' => 'uuid',
                'filename' => 'string',
                'file_path' => 'string',
                'is_active' => 'boolean',
                'created_at' => 'timestamp'
            ],
            'auditor_certifications' => [
                'id' => 'uuid',
                'subcategory_id' => 'uuid',
                'signature_name' => 'string',
                'certified_at' => 'timestamp'
            ],
            'judge_score_removal_requests' => [
                'id' => 'uuid',
                'subcategory_id' => 'uuid',
                'contestant_id' => 'uuid',
                'judge_id' => 'uuid',
                'reason' => 'string',
                'requested_by' => 'uuid',
                'requested_at' => 'timestamp',
                'status' => 'string',
                'approved_by' => 'uuid',
                'approved_at' => 'timestamp'
            ],
            'judge_comments' => [
                'id' => 'uuid',
                'subcategory_id' => 'uuid',
                'contestant_id' => 'uuid',
                'judge_id' => 'uuid',
                'comment' => 'string',
                'created_at' => 'timestamp'
            ],
            'tally_master_certifications' => [
                'id' => 'uuid',
                'subcategory_id' => 'uuid',
                'signature_name' => 'string',
                'certified_at' => 'timestamp'
            ],
            'subcategory_templates' => [
                'id' => 'uuid',
                'name' => 'string',
                'description' => 'string',
                'subcategory_names' => 'string',
                'max_score' => 'integer'
            ],
            'template_criteria' => [
                'id' => 'uuid',
                'template_id' => 'uuid',
                'name' => 'string',
                'max_score' => 'integer'
            ],
            'overall_deductions' => [
                'id' => 'uuid',
                'subcategory_id' => 'uuid',
                'contestant_id' => 'uuid',
                'amount' => 'decimal',
                'comment' => 'string',
                'signature_name' => 'string',
                'signed_at' => 'timestamp',
                'created_by' => 'uuid',
                'created_at' => 'timestamp'
            ]
        ];
    }

    /**
     * Migrate all data from SQLite to PostgreSQL
     */
    public function migrateData(): bool {
        try {
            $this->log("Starting data migration...");
            
            // Get tables in dependency order
            $tables = $this->getTableMigrationOrder();
            
            foreach ($tables as $tableName) {
                $this->migrateTable($tableName);
            }
            
            $this->log("Data migration completed successfully!");
            return true;
            
        } catch (\Exception $e) {
            $this->log("Data migration failed: " . $e->getMessage(), 'error');
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    /**
     * Get tables in migration order (respecting foreign key dependencies)
     */
    private function getTableMigrationOrder(): array {
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
     * Migrate individual table data
     */
    private function migrateTable(string $tableName): void {
        $this->log("Migrating table: {$tableName}");
        
        // Check if table exists in source
        $sourceTables = $this->sourceDb->getTables();
        if (!in_array($tableName, $sourceTables)) {
            $this->log("Table {$tableName} does not exist in source database", 'warning');
            return;
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM {$tableName}";
        $totalRows = (int) $this->sourceDb->fetchColumn($countSql);
        
        if ($totalRows === 0) {
            $this->log("Table {$tableName} is empty, skipping");
            return;
        }
        
        $this->log("Migrating {$totalRows} rows from {$tableName}");
        
        // Migrate in batches
        $offset = 0;
        $migratedRows = 0;
        
        while ($offset < $totalRows) {
            $batch = $this->getTableBatch($tableName, $offset, $this->batchSize);
            
            if (empty($batch)) {
                break;
            }
            
            $this->insertBatch($tableName, $batch);
            $migratedRows += count($batch);
            $offset += $this->batchSize;
            
            $this->log("Migrated {$migratedRows}/{$totalRows} rows from {$tableName}");
        }
        
        $this->log("Table {$tableName} migration completed ({$migratedRows} rows)");
    }

    /**
     * Get batch of data from source table
     */
    private function getTableBatch(string $tableName, int $offset, int $limit): array {
        $sql = "SELECT * FROM {$tableName} LIMIT {$limit} OFFSET {$offset}";
        return $this->sourceDb->query($sql);
    }

    /**
     * Insert batch of data into target table
     */
    private function insertBatch(string $tableName, array $batch): void {
        if (empty($batch)) {
            return;
        }
        
        // Transform data according to mappings
        $transformedBatch = [];
        foreach ($batch as $row) {
            $transformedBatch[] = $this->transformRow($tableName, $row);
        }
        
        // Build insert SQL
        $columns = array_keys($transformedBatch[0]);
        $placeholders = ':' . implode(', :', $columns);
        $sql = "INSERT INTO {$tableName} (" . implode(', ', $columns) . ") VALUES ({$placeholders})";
        
        // Execute batch insert
        $this->targetDb->beginTransaction();
        
        try {
            foreach ($transformedBatch as $row) {
                $this->targetDb->execute($sql, $row);
            }
            
            $this->targetDb->commit();
            
        } catch (\Exception $e) {
            $this->targetDb->rollback();
            throw new \Exception("Failed to insert batch into {$tableName}: " . $e->getMessage());
        }
    }

    /**
     * Transform row data according to type mappings
     */
    private function transformRow(string $tableName, array $row): array {
        $transformed = [];
        $mappings = $this->dataTypeMappings[$tableName] ?? [];
        
        foreach ($row as $column => $value) {
            $type = $mappings[$column] ?? 'string';
            $transformed[$column] = $this->transformValue($value, $type);
        }
        
        return $transformed;
    }

    /**
     * Transform individual value according to type
     */
    private function transformValue(mixed $value, string $type): mixed {
        if ($value === null) {
            return null;
        }
        
        switch ($type) {
            case 'uuid':
                // Convert SQLite TEXT IDs to PostgreSQL UUIDs
                if (is_string($value) && strlen($value) === 32) {
                    // Convert hex string to UUID format
                    return substr($value, 0, 8) . '-' . 
                           substr($value, 8, 4) . '-' . 
                           substr($value, 12, 4) . '-' . 
                           substr($value, 16, 4) . '-' . 
                           substr($value, 20, 12);
                }
                return $value;
                
            case 'boolean':
                // Convert SQLite INTEGER booleans to PostgreSQL BOOLEAN
                if (is_numeric($value)) {
                    return (bool) $value;
                }
                if (is_string($value)) {
                    return strtolower($value) === 'true' || $value === '1';
                }
                return (bool) $value;
                
            case 'integer':
                return (int) $value;
                
            case 'decimal':
                return (float) $value;
                
            case 'timestamp':
                // Convert SQLite TEXT timestamps to PostgreSQL TIMESTAMP
                if (is_string($value)) {
                    try {
                        $date = new \DateTime($value);
                        return $date->format('Y-m-d H:i:s');
                    } catch (\Exception $e) {
                        // If parsing fails, return current timestamp
                        return date('Y-m-d H:i:s');
                    }
                }
                return $value;
                
            case 'string':
            default:
                // Ensure string values are properly encoded
                return (string) $value;
        }
    }

    /**
     * Validate migrated data integrity
     */
    public function validateMigration(): array {
        $this->log("Validating migrated data...");
        $issues = [];
        
        $tables = $this->getTableMigrationOrder();
        
        foreach ($tables as $tableName) {
            $sourceCount = $this->getTableCount($this->sourceDb, $tableName);
            $targetCount = $this->getTableCount($this->targetDb, $tableName);
            
            if ($sourceCount !== $targetCount) {
                $issue = "Row count mismatch in {$tableName}: source={$sourceCount}, target={$targetCount}";
                $issues[] = $issue;
                $this->log($issue, 'error');
            } else {
                $this->log("Row count validation passed for {$tableName}: {$sourceCount} rows");
            }
        }
        
        if (empty($issues)) {
            $this->log("Data validation completed successfully!");
        } else {
            $this->log("Data validation found " . count($issues) . " issues", 'error');
        }
        
        return $issues;
    }

    /**
     * Get table row count
     */
    private function getTableCount(DatabaseInterface $db, string $tableName): int {
        try {
            $sql = "SELECT COUNT(*) FROM {$tableName}";
            return (int) $db->fetchColumn($sql);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Create data integrity checks
     */
    public function createIntegrityChecks(): void {
        $this->log("Creating data integrity checks...");
        
        $checks = [
            // Check foreign key relationships
            "SELECT COUNT(*) FROM categories c LEFT JOIN contests co ON c.contest_id = co.id WHERE co.id IS NULL",
            "SELECT COUNT(*) FROM subcategories s LEFT JOIN categories c ON s.category_id = c.id WHERE c.id IS NULL",
            "SELECT COUNT(*) FROM scores sc LEFT JOIN subcategories s ON sc.subcategory_id = s.id WHERE s.id IS NULL",
            "SELECT COUNT(*) FROM scores sc LEFT JOIN contestants c ON sc.contestant_id = c.id WHERE c.id IS NULL",
            "SELECT COUNT(*) FROM scores sc LEFT JOIN judges j ON sc.judge_id = j.id WHERE j.id IS NULL",
            "SELECT COUNT(*) FROM scores sc LEFT JOIN criteria cr ON sc.criterion_id = cr.id WHERE cr.id IS NULL",
            "SELECT COUNT(*) FROM users u LEFT JOIN judges j ON u.judge_id = j.id WHERE u.judge_id IS NOT NULL AND j.id IS NULL",
            "SELECT COUNT(*) FROM users u LEFT JOIN contestants c ON u.contestant_id = c.id WHERE u.contestant_id IS NOT NULL AND c.id IS NULL",
            
            // Check data consistency
            "SELECT COUNT(*) FROM scores WHERE score < 0 OR score > 100",
            "SELECT COUNT(*) FROM criteria WHERE max_score <= 0",
            "SELECT COUNT(*) FROM contestants WHERE contestant_number < 0",
            "SELECT COUNT(*) FROM overall_deductions WHERE amount < 0",
            
            // Check required fields
            "SELECT COUNT(*) FROM contests WHERE name IS NULL OR name = ''",
            "SELECT COUNT(*) FROM categories WHERE name IS NULL OR name = ''",
            "SELECT COUNT(*) FROM subcategories WHERE name IS NULL OR name = ''",
            "SELECT COUNT(*) FROM contestants WHERE name IS NULL OR name = ''",
            "SELECT COUNT(*) FROM judges WHERE name IS NULL OR name = ''",
            "SELECT COUNT(*) FROM users WHERE name IS NULL OR name = ''",
            "SELECT COUNT(*) FROM users WHERE role IS NULL OR role = ''",
        ];
        
        foreach ($checks as $check) {
            try {
                $count = (int) $this->targetDb->fetchColumn($check);
                if ($count > 0) {
                    $this->log("Data integrity check failed: " . substr($check, 0, 50) . "... ({$count} issues)", 'warning');
                }
            } catch (\Exception $e) {
                $this->log("Failed to run integrity check: " . $e->getMessage(), 'warning');
            }
        }
        
        $this->log("Data integrity checks completed");
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

    /**
     * Set batch size for migration
     */
    public function setBatchSize(int $batchSize): void {
        $this->batchSize = $batchSize;
    }
}
