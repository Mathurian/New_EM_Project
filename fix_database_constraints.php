<?php
/**
 * Comprehensive fix for old_users table and foreign key issues
 * This script resolves the "no such table: main.old_users" error
 */

require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/helpers.php';

try {
    $pdo = App\DB::pdo();
    
    echo "=== Fixing old_users table and foreign key issues ===\n";
    
    // Step 1: Disable foreign key constraints temporarily
    echo "1. Disabling foreign key constraints...\n";
    $pdo->exec("PRAGMA foreign_keys=OFF");
    echo "   ✅ Foreign key constraints disabled\n";
    
    // Step 2: Check for and drop any leftover migration tables
    echo "2. Checking for leftover migration tables...\n";
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND (name LIKE '%_new' OR name LIKE 'old_%' OR name LIKE '%_backup' OR name LIKE '%_temp')");
    $leftoverTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($leftoverTables)) {
        echo "   Found leftover tables: " . implode(', ', $leftoverTables) . "\n";
        foreach ($leftoverTables as $table) {
            if ($table !== 'users') { // Don't drop the main users table
                echo "   Dropping table: $table\n";
                $pdo->exec("DROP TABLE IF EXISTS $table");
            }
        }
        echo "   ✅ Leftover tables cleaned up\n";
    } else {
        echo "   ✅ No leftover migration tables found\n";
    }
    
    // Step 3: Verify the main users table is intact
    echo "3. Verifying main users table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   ✅ Main users table has $userCount users\n";
    
    // Step 4: Check for any foreign key constraint issues
    echo "4. Checking foreign key constraints...\n";
    $stmt = $pdo->query("PRAGMA foreign_key_list(activity_logs)");
    $fkList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($fkList)) {
        echo "   Found foreign key constraints:\n";
        $needsFix = false;
        foreach ($fkList as $fk) {
            echo "   - {$fk['table']}.{$fk['from']} -> {$fk['table']}.{$fk['to']}\n";
            if ($fk['table'] === 'old_users') {
                $needsFix = true;
            }
        }
        
        if ($needsFix) {
            echo "   ❌ Found foreign key constraint pointing to old_users table\n";
            echo "   🔧 Fixing foreign key constraint...\n";
            
            // Backup existing activity_logs data
            $stmt = $pdo->query("SELECT * FROM activity_logs");
            $activityLogsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "   📦 Backed up " . count($activityLogsData) . " activity log records\n";
            
            // Drop and recreate activity_logs table with correct foreign key
            $pdo->exec("DROP TABLE activity_logs");
            echo "   🗑️  Dropped old activity_logs table\n";
            
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
            echo "   ✅ Recreated activity_logs table with correct foreign key\n";
            
            // Restore data
            if (!empty($activityLogsData)) {
                $stmt = $pdo->prepare("
                    INSERT INTO activity_logs (id, user_id, user_name, user_role, action, resource_type, resource_id, details, ip_address, user_agent, log_level, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($activityLogsData as $log) {
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
                }
                echo "   ✅ Restored " . count($activityLogsData) . " activity log records\n";
            }
        } else {
            echo "   ✅ Foreign key constraints are properly configured\n";
        }
    } else {
        echo "   ⚠️  No foreign key constraints found (this might be expected)\n";
    }
    
    // Step 5: Re-enable foreign key constraints
    echo "5. Re-enabling foreign key constraints...\n";
    $pdo->exec("PRAGMA foreign_keys=ON");
    echo "   ✅ Foreign key constraints re-enabled\n";
    
    // Step 6: Test a simple insert to verify everything works
    echo "6. Testing database operations...\n";
    try {
        // Test inserting into activity_logs (this was failing before)
        $testId = App\uuid();
        $stmt = $pdo->prepare("INSERT INTO activity_logs (id, user_id, user_name, user_role, action, resource_type, resource_id, details, ip_address, user_agent, log_level, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $testId,
            null, // user_id can be null
            'Test User',
            'test',
            'test_action',
            'test',
            'test_id',
            'Testing database fix',
            '127.0.0.1',
            'Test Agent',
            'info',
            date('c')
        ]);
        
        // Clean up test record
        $pdo->exec("DELETE FROM activity_logs WHERE id = '$testId'");
        
        echo "   ✅ Database operations working correctly\n";
    } catch (Exception $e) {
        echo "   ❌ Database test failed: " . $e->getMessage() . "\n";
        throw $e;
    }
    
    echo "\n=== Fix Complete ===\n";
    echo "✅ old_users table issue resolved\n";
    echo "✅ Foreign key constraints fixed\n";
    echo "✅ Database operations verified\n";
    echo "✅ File uploads should now work properly\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
