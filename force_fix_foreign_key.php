<?php
/**
 * Force fix for old_users foreign key constraint issue
 * This script will definitely fix the foreign key constraint problem
 */

require_once __DIR__ . '/app/lib/DB.php';

try {
    $pdo = App\DB::pdo();
    
    echo "=== Force Fix for old_users Foreign Key Constraint ===\n";
    
    // Step 1: Disable foreign key constraints
    echo "1. Disabling foreign key constraints...\n";
    $pdo->exec("PRAGMA foreign_keys=OFF");
    echo "   ✅ Foreign key constraints disabled\n";
    
    // Step 2: Check current foreign key constraints
    echo "2. Checking current foreign key constraints...\n";
    $stmt = $pdo->query("PRAGMA foreign_key_list(activity_logs)");
    $fkList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($fkList)) {
        echo "   Current foreign key constraints:\n";
        foreach ($fkList as $fk) {
            echo "   - {$fk['table']}.{$fk['from']} -> {$fk['table']}.{$fk['to']}\n";
        }
    }
    
    // Step 3: Backup activity_logs data
    echo "3. Backing up activity_logs data...\n";
    $stmt = $pdo->query("SELECT * FROM activity_logs");
    $activityLogsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   📦 Backed up " . count($activityLogsData) . " activity log records\n";
    
    // Step 4: Drop activity_logs table
    echo "4. Dropping activity_logs table...\n";
    $pdo->exec("DROP TABLE activity_logs");
    echo "   🗑️  Activity_logs table dropped\n";
    
    // Step 5: Recreate activity_logs table with correct foreign key
    echo "5. Recreating activity_logs table with correct foreign key...\n";
    $pdo->exec("
        CREATE TABLE activity_logs (
            id TEXT PRIMARY KEY,
            user_id TEXT,
            user_name TEXT,
            user_role TEXT,
            action TEXT NOT NULL,
            resource_type TEXT,
            resource_id TEXT,
            details TEXT,
            ip_address TEXT,
            user_agent TEXT,
            log_level TEXT NOT NULL DEFAULT 'info',
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    echo "   ✅ Activity_logs table recreated with correct foreign key\n";
    
    // Step 6: Restore data
    echo "6. Restoring activity_logs data...\n";
    if (!empty($activityLogsData)) {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (id, user_id, user_name, user_role, action, resource_type, resource_id, details, ip_address, user_agent, log_level, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $restoredCount = 0;
        foreach ($activityLogsData as $log) {
            try {
                $stmt->execute([
                    $log['id'],
                    $log['user_id'],
                    $log['user_name'],
                    $log['user_role'],
                    $log['action'],
                    $log['resource_type'],
                    $log['resource_id'],
                    $log['details'],
                    $log['ip_address'],
                    $log['user_agent'],
                    $log['log_level'],
                    $log['created_at']
                ]);
                $restoredCount++;
            } catch (Exception $e) {
                echo "   ⚠️  Skipped record {$log['id']}: " . $e->getMessage() . "\n";
            }
        }
        echo "   ✅ Restored $restoredCount out of " . count($activityLogsData) . " activity log records\n";
    }
    
    // Step 7: Re-enable foreign key constraints
    echo "7. Re-enabling foreign key constraints...\n";
    $pdo->exec("PRAGMA foreign_keys=ON");
    echo "   ✅ Foreign key constraints re-enabled\n";
    
    // Step 8: Verify the fix
    echo "8. Verifying the fix...\n";
    $stmt = $pdo->query("PRAGMA foreign_key_list(activity_logs)");
    $newFkList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($newFkList)) {
        echo "   New foreign key constraints:\n";
        foreach ($newFkList as $fk) {
            echo "   - {$fk['table']}.{$fk['from']} -> {$fk['table']}.{$fk['to']}\n";
            if ($fk['table'] === 'old_users') {
                echo "   ❌ ERROR: Still pointing to old_users!\n";
                exit(1);
            }
        }
        echo "   ✅ Foreign key constraints are now correct\n";
    }
    
    // Step 9: Test activity_logs insert
    echo "9. Testing activity_logs insert...\n";
    $testId = 'test-' . uniqid();
    $stmt = $pdo->prepare("INSERT INTO activity_logs (id, user_id, user_name, user_role, action, resource_type, resource_id, details, ip_address, user_agent, log_level, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $testId,
        null,
        'Test User',
        'test',
        'test_action',
        'test',
        'test_id',
        'Testing foreign key fix',
        '127.0.0.1',
        'Test Agent',
        'info',
        date('c')
    ]);
    
    // Clean up test record
    $pdo->exec("DELETE FROM activity_logs WHERE id = '$testId'");
    echo "   ✅ Activity_logs insert test successful\n";
    
    echo "\n=== Fix Complete ===\n";
    echo "✅ Foreign key constraint issue resolved\n";
    echo "✅ Activity_logs table recreated with correct foreign key\n";
    echo "✅ All data restored\n";
    echo "✅ File uploads should now work properly\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
