<?php
/**
 * Command-line script to fix backup_settings constraint
 * Run this outside of the web server to avoid database locks
 */

require_once __DIR__ . '/app/bootstrap.php';

function fixConstraint() {
    $maxRetries = 10;
    $retryDelay = 2; // seconds
    
    echo "Starting constraint fix...\n";
    
    for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
        try {
            echo "Attempt $attempt of $maxRetries...\n";
            
            // Create a completely new database connection
            $dbPath = App\DB::getDatabasePath();
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Set aggressive timeout settings
            $pdo->exec('PRAGMA journal_mode=DELETE');
            $pdo->exec('PRAGMA busy_timeout=60000'); // 60 seconds
            $pdo->exec('PRAGMA synchronous=NORMAL');
            $pdo->exec('PRAGMA cache_size=10000');
            $pdo->exec('PRAGMA temp_store=MEMORY');
            
            // Try to get an immediate lock
            $pdo->exec('BEGIN IMMEDIATE');
            
            // Create new table with updated constraint
            $pdo->exec('CREATE TABLE backup_settings_new (
                id TEXT PRIMARY KEY,
                backup_type TEXT NOT NULL CHECK (backup_type IN (\'schema\', \'full\')),
                enabled BOOLEAN NOT NULL DEFAULT 0,
                frequency TEXT NOT NULL CHECK (frequency IN (\'minutes\', \'hours\', \'daily\', \'weekly\', \'monthly\')),
                frequency_value INTEGER NOT NULL DEFAULT 1,
                retention_days INTEGER NOT NULL DEFAULT 30,
                last_run TEXT,
                next_run TEXT,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )');
            
            // Copy data from old table
            $pdo->exec('INSERT INTO backup_settings_new SELECT * FROM backup_settings');
            
            // Drop old table and rename new one
            $pdo->exec('DROP TABLE backup_settings');
            $pdo->exec('ALTER TABLE backup_settings_new RENAME TO backup_settings');
            
            $pdo->commit();
            
            echo "Constraint update completed successfully!\n";
            return true;
            
        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            
            echo "Attempt $attempt failed: " . $e->getMessage() . "\n";
            
            if ($attempt < $maxRetries) {
                echo "Waiting $retryDelay seconds before retry...\n";
                sleep($retryDelay);
                $retryDelay *= 1.5; // Exponential backoff
            } else {
                echo "All attempts failed. Database may be heavily locked.\n";
                echo "Try stopping the web server temporarily and running this script again.\n";
                return false;
            }
        }
    }
    
    return false;
}

// Test the constraint after fixing
function testConstraint() {
    try {
        $dbPath = App\DB::getDatabasePath();
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "\nTesting constraint...\n";
        
        $frequencies = ['minutes', 'hours', 'daily', 'weekly', 'monthly'];
        
        foreach ($frequencies as $freq) {
            try {
                $stmt = $pdo->prepare('INSERT INTO backup_settings (id, backup_type, enabled, frequency, frequency_value, retention_days) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([uniqid(), 'schema', 0, $freq, 1, 30]);
                
                // Clean up test record
                $pdo->prepare('DELETE FROM backup_settings WHERE backup_type = ? AND frequency = ?')->execute(['schema', $freq]);
                
                echo "$freq: SUCCESS\n";
            } catch (Exception $e) {
                echo "$freq: FAILED - " . $e->getMessage() . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "Error testing constraint: " . $e->getMessage() . "\n";
    }
}

// Run the fix
if (fixConstraint()) {
    testConstraint();
} else {
    echo "Fix failed. Please try again later.\n";
    exit(1);
}
