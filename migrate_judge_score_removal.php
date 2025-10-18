<?php
/**
 * Create judge score removal requests table
 * This table tracks requests to remove judge scores with proper authorization
 */

require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/Logger.php';
require_once __DIR__ . '/app/lib/helpers.php';

use App\DB;
use App\Logger;

echo "Creating Judge Score Removal Requests table\n";
echo "==========================================\n\n";

try {
    $pdo = DB::pdo();
    
    // Create judge_score_removal_requests table
    $sql = "
        CREATE TABLE IF NOT EXISTS judge_score_removal_requests (
            id TEXT PRIMARY KEY,
            judge_id TEXT NOT NULL,
            requested_by TEXT NOT NULL,
            reason TEXT NOT NULL,
            status TEXT NOT NULL DEFAULT 'pending',
            created_at TEXT NOT NULL,
            updated_at TEXT,
            auditor_signature TEXT,
            auditor_signed_at TEXT,
            tally_master_signature TEXT,
            tally_master_signed_at TEXT,
            head_judge_signature TEXT,
            head_judge_signed_at TEXT,
            completed_at TEXT,
            FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE,
            FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE
        );
    ";
    $pdo->exec($sql);
    Logger::info('migration', 'judge_score_removal_requests_table_created', null, "Judge score removal requests table created or already exists.");
    echo "Judge score removal requests table created or already exists.\n";

    // Add indexes for better performance
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_judge_score_removal_requests_judge_id ON judge_score_removal_requests (judge_id);",
        "CREATE INDEX IF NOT EXISTS idx_judge_score_removal_requests_status ON judge_score_removal_requests (status);",
        "CREATE INDEX IF NOT EXISTS idx_judge_score_removal_requests_created_at ON judge_score_removal_requests (created_at);"
    ];
    
    foreach ($indexes as $indexSql) {
        $pdo->exec($indexSql);
    }
    
    Logger::info('migration', 'judge_score_removal_requests_indexes_created', null, "Indexes for judge score removal requests created or already exist.");
    echo "Indexes created or already exist.\n";

    echo "Judge Score Removal Requests migration completed successfully.\n";
    Logger::info('migration', 'judge_score_removal_requests_migration_success', null, "Judge Score Removal Requests migration completed successfully.");

} catch (\PDOException $e) {
    Logger::error('migration', 'judge_score_removal_requests_migration_failed', null, "Judge Score Removal Requests migration failed: " . $e->getMessage());
    echo "Judge Score Removal Requests migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
