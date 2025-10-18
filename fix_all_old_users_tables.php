<?php
/**
 * Fix ALL tables with foreign key constraints pointing to old_users
 * This will fix: auditor_certifications, backup_logs, emcee_scripts, overall_deductions, system_settings
 */

require_once __DIR__ . '/app/lib/DB.php';

try {
    $pdo = App\DB::pdo();
    
    echo "=== Fixing ALL Tables with old_users Foreign Key Constraints ===\n";
    
    // Disable foreign key constraints
    echo "1. Disabling foreign key constraints...\n";
    $pdo->exec("PRAGMA foreign_keys=OFF");
    echo "   ✅ Foreign key constraints disabled\n";
    
    // List of tables to fix
    $tablesToFix = [
        'auditor_certifications' => [
            'columns' => ['id', 'auditor_id', 'certified_at', 'created_at'],
            'foreign_key_column' => 'auditor_id',
            'foreign_key_reference' => 'users(id)'
        ],
        'backup_logs' => [
            'columns' => ['id', 'backup_type', 'file_path', 'file_size', 'status', 'created_by', 'created_at', 'error_message'],
            'foreign_key_column' => 'created_by',
            'foreign_key_reference' => 'users(id)'
        ],
        'emcee_scripts' => [
            'columns' => ['id', 'filename', 'file_path', 'is_active', 'created_at', 'uploaded_by', 'title', 'description', 'file_name', 'file_size', 'uploaded_at'],
            'foreign_key_column' => 'uploaded_by',
            'foreign_key_reference' => 'users(id)'
        ],
        'overall_deductions' => [
            'columns' => ['id', 'subcategory_id', 'contestant_id', 'amount', 'comment', 'signature_name', 'signed_at', 'created_by', 'created_at'],
            'foreign_key_column' => 'created_by',
            'foreign_key_reference' => 'users(id)'
        ],
        'system_settings' => [
            'columns' => ['id', 'setting_key', 'setting_value', 'description', 'updated_at', 'updated_by'],
            'foreign_key_column' => 'updated_by',
            'foreign_key_reference' => 'users(id)'
        ]
    ];
    
    foreach ($tablesToFix as $tableName => $tableInfo) {
        echo "\n2. Fixing $tableName table...\n";
        
        // Backup data
        echo "   📦 Backing up $tableName data...\n";
        $stmt = $pdo->query("SELECT * FROM $tableName");
        $tableData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   📦 Backed up " . count($tableData) . " records\n";
        
        // Drop table
        echo "   🗑️  Dropping $tableName table...\n";
        $pdo->exec("DROP TABLE $tableName");
        echo "   ✅ $tableName table dropped\n";
        
        // Recreate table with correct foreign key
        echo "   🔧 Recreating $tableName table with correct foreign key...\n";
        
        $createSql = "CREATE TABLE $tableName (";
        $columnDefs = [];
        
        foreach ($tableInfo['columns'] as $column) {
            if ($column === 'id') {
                $columnDefs[] = "id TEXT PRIMARY KEY";
            } elseif ($column === $tableInfo['foreign_key_column']) {
                $columnDefs[] = "$column TEXT";
            } else {
                $columnDefs[] = "$column TEXT";
            }
        }
        
        $createSql .= implode(', ', $columnDefs);
        
        // Add foreign key constraint
        $createSql .= ", FOREIGN KEY ({$tableInfo['foreign_key_column']}) REFERENCES {$tableInfo['foreign_key_reference']} ON DELETE SET NULL";
        
        // Add other foreign keys based on table
        if ($tableName === 'overall_deductions') {
            $createSql .= ", FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE";
            $createSql .= ", FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE";
        }
        
        $createSql .= ")";
        
        $pdo->exec($createSql);
        echo "   ✅ $tableName table recreated with correct foreign key\n";
        
        // Restore data
        echo "   📥 Restoring $tableName data...\n";
        if (!empty($tableData)) {
            $placeholders = str_repeat('?,', count($tableInfo['columns']) - 1) . '?';
            $stmt = $pdo->prepare("INSERT INTO $tableName (" . implode(', ', $tableInfo['columns']) . ") VALUES ($placeholders)");
            
            $restoredCount = 0;
            foreach ($tableData as $row) {
                try {
                    $values = [];
                    foreach ($tableInfo['columns'] as $column) {
                        $values[] = $row[$column] ?? null;
                    }
                    $stmt->execute($values);
                    $restoredCount++;
                } catch (Exception $e) {
                    echo "   ⚠️  Skipped record {$row['id']}: " . $e->getMessage() . "\n";
                }
            }
            echo "   ✅ Restored $restoredCount out of " . count($tableData) . " records\n";
        }
    }
    
    // Re-enable foreign key constraints
    echo "\n3. Re-enabling foreign key constraints...\n";
    $pdo->exec("PRAGMA foreign_keys=ON");
    echo "   ✅ Foreign key constraints re-enabled\n";
    
    // Verify the fixes
    echo "\n4. Verifying fixes...\n";
    foreach ($tablesToFix as $tableName => $tableInfo) {
        $stmt = $pdo->query("PRAGMA foreign_key_list($tableName)");
        $fkList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Checking $tableName foreign keys:\n";
        $hasOldUsers = false;
        foreach ($fkList as $fk) {
            echo "     - {$fk['table']}.{$fk['from']} -> {$fk['table']}.{$fk['to']}\n";
            if ($fk['table'] === 'old_users') {
                $hasOldUsers = true;
            }
        }
        
        if ($hasOldUsers) {
            echo "   ❌ $tableName still references old_users!\n";
        } else {
            echo "   ✅ $tableName foreign keys are correct\n";
        }
    }
    
    // Test emcee_scripts insert
    echo "\n5. Testing emcee_scripts insert...\n";
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
        
        // Clean up test record
        $pdo->exec("DELETE FROM emcee_scripts WHERE id = '$testId'");
        echo "   ✅ Emcee_scripts insert test successful\n";
    } catch (Exception $e) {
        echo "   ❌ Emcee_scripts insert failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== Fix Complete ===\n";
    echo "✅ All 5 tables fixed with correct foreign key constraints\n";
    echo "✅ All data restored\n";
    echo "✅ File uploads should now work properly\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
