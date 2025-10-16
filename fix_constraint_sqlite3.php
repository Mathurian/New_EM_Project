<?php
/**
 * Simple script to fix backup_settings constraint using sqlite3 command line
 * This avoids all PHP PDO transaction issues
 */

require_once __DIR__ . '/app/bootstrap.php';

function fixConstraintWithSqlite3() {
    $dbPath = App\DB::getDatabasePath();
    
    if (!file_exists($dbPath)) {
        echo "Error: Database file not found at: $dbPath\n";
        return false;
    }
    
    echo "Database path: $dbPath\n";
    echo "Starting constraint fix using sqlite3 command line...\n";
    
    // Create a temporary SQL file
    $sqlFile = tempnam(sys_get_temp_dir(), 'fix_constraint_');
    $sql = "
-- Create new table with updated constraint
CREATE TABLE backup_settings_new (
    id TEXT PRIMARY KEY,
    backup_type TEXT NOT NULL CHECK (backup_type IN ('schema', 'full')),
    enabled BOOLEAN NOT NULL DEFAULT 0,
    frequency TEXT NOT NULL CHECK (frequency IN ('minutes', 'hours', 'daily', 'weekly', 'monthly')),
    frequency_value INTEGER NOT NULL DEFAULT 1,
    retention_days INTEGER NOT NULL DEFAULT 30,
    last_run TEXT,
    next_run TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Copy data from old table
INSERT INTO backup_settings_new SELECT * FROM backup_settings;

-- Drop old table and rename new one
DROP TABLE backup_settings;
ALTER TABLE backup_settings_new RENAME TO backup_settings;
";
    
    file_put_contents($sqlFile, $sql);
    
    // Execute the SQL using sqlite3 command line
    $command = "sqlite3 \"$dbPath\" < \"$sqlFile\" 2>&1";
    echo "Executing: $command\n";
    
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    // Clean up temp file
    unlink($sqlFile);
    
    echo "Output:\n";
    foreach ($output as $line) {
        echo "$line\n";
    }
    
    if ($returnCode === 0) {
        echo "Constraint update completed successfully!\n";
        
        // Test the constraint
        echo "\nTesting constraint...\n";
        $testCommand = "sqlite3 \"$dbPath\" \"INSERT INTO backup_settings (id, backup_type, enabled, frequency, frequency_value, retention_days) VALUES ('test-schema', 'schema', 0, 'minutes', 1, 30); DELETE FROM backup_settings WHERE id = 'test-schema';\" 2>&1";
        
        $testOutput = [];
        $testReturnCode = 0;
        exec($testCommand, $testOutput, $testReturnCode);
        
        if ($testReturnCode === 0) {
            echo "Constraint test: SUCCESS\n";
        } else {
            echo "Constraint test: FAILED\n";
            foreach ($testOutput as $line) {
                echo "$line\n";
            }
        }
        
        return true;
    } else {
        echo "Constraint update failed with return code: $returnCode\n";
        return false;
    }
}

// Run the fix
if (fixConstraintWithSqlite3()) {
    echo "\nFix completed successfully!\n";
} else {
    echo "\nFix failed.\n";
    exit(1);
}
