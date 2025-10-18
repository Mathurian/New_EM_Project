<?php
/**
 * Comprehensive check for ALL foreign key constraints referencing old_users
 * This will find any remaining references to the old_users table
 */

require_once __DIR__ . '/app/lib/DB.php';

try {
    $pdo = App\DB::pdo();
    
    echo "=== Comprehensive Foreign Key Constraint Check ===\n";
    
    // Get all tables in the database
    echo "1. Getting all tables in database...\n";
    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "   Found " . count($tables) . " tables:\n";
    foreach ($tables as $table) {
        echo "   - $table\n";
    }
    
    // Check foreign key constraints for each table
    echo "\n2. Checking foreign key constraints for each table...\n";
    $problematicTables = [];
    
    foreach ($tables as $table) {
        echo "   Checking $table...\n";
        $stmt = $pdo->query("PRAGMA foreign_key_list($table)");
        $fkList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($fkList)) {
            echo "     Foreign key constraints:\n";
            foreach ($fkList as $fk) {
                echo "     - {$fk['table']}.{$fk['from']} -> {$fk['table']}.{$fk['to']}\n";
                if ($fk['table'] === 'old_users') {
                    $problematicTables[] = $table;
                    echo "     ❌ PROBLEM: $table references old_users!\n";
                }
            }
        } else {
            echo "     No foreign key constraints\n";
        }
    }
    
    // Check for any views that might reference old_users
    echo "\n3. Checking for views referencing old_users...\n";
    $stmt = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='view'");
    $views = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($views)) {
        foreach ($views as $view) {
            if (strpos($view['sql'], 'old_users') !== false) {
                echo "   ❌ PROBLEM: View {$view['name']} references old_users!\n";
                echo "   SQL: {$view['sql']}\n";
            }
        }
    } else {
        echo "   No views found\n";
    }
    
    // Check for any triggers that might reference old_users
    echo "\n4. Checking for triggers referencing old_users...\n";
    $stmt = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='trigger'");
    $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($triggers)) {
        foreach ($triggers as $trigger) {
            if (strpos($trigger['sql'], 'old_users') !== false) {
                echo "   ❌ PROBLEM: Trigger {$trigger['name']} references old_users!\n";
                echo "   SQL: {$trigger['sql']}\n";
            }
        }
    } else {
        echo "   No triggers found\n";
    }
    
    // Check for any indexes that might reference old_users
    echo "\n5. Checking for indexes referencing old_users...\n";
    $stmt = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='index' AND sql IS NOT NULL");
    $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($indexes)) {
        foreach ($indexes as $index) {
            if (strpos($index['sql'], 'old_users') !== false) {
                echo "   ❌ PROBLEM: Index {$index['name']} references old_users!\n";
                echo "   SQL: {$index['sql']}\n";
            }
        }
    } else {
        echo "   No custom indexes found\n";
    }
    
    // Summary
    echo "\n=== Summary ===\n";
    if (!empty($problematicTables)) {
        echo "❌ Found " . count($problematicTables) . " tables with foreign key constraints pointing to old_users:\n";
        foreach ($problematicTables as $table) {
            echo "   - $table\n";
        }
        echo "\nThese tables need to be fixed.\n";
    } else {
        echo "✅ No tables found with foreign key constraints pointing to old_users\n";
    }
    
    // Test a simple insert to see what happens
    echo "\n6. Testing simple insert to identify the problem...\n";
    try {
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
            'Testing foreign key constraints',
            '127.0.0.1',
            'Test Agent',
            'info',
            date('c')
        ]);
        
        // Clean up
        $pdo->exec("DELETE FROM activity_logs WHERE id = '$testId'");
        echo "   ✅ Activity_logs insert test successful\n";
    } catch (Exception $e) {
        echo "   ❌ Activity_logs insert failed: " . $e->getMessage() . "\n";
    }
    
    // Test emcee_scripts insert
    echo "\n7. Testing emcee_scripts insert...\n";
    try {
        $testId = 'test-' . uniqid();
        $stmt = $pdo->prepare("INSERT INTO emcee_scripts (id, filename, file_path, is_active, created_at, uploaded_by, title, description, file_name, file_size, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $testId,
            'test.pdf',
            '/uploads/test.pdf',
            1,
            date('Y-m-d H:i:s'),
            '45a63c33a756a437d0d99785a8a444fb', // Use the actual user ID from the error
            'Test Script',
            'Test Description',
            'test.pdf',
            1024,
            date('Y-m-d H:i:s')
        ]);
        
        // Clean up
        $pdo->exec("DELETE FROM emcee_scripts WHERE id = '$testId'");
        echo "   ✅ Emcee_scripts insert test successful\n";
    } catch (Exception $e) {
        echo "   ❌ Emcee_scripts insert failed: " . $e->getMessage() . "\n";
        echo "   This is likely where the old_users error is coming from!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
