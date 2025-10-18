<?php
/**
 * Update users table CHECK constraint to include Board user type
 * This fixes the issue where new user types can't be created due to CHECK constraint
 */

require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/helpers.php';

echo "Updating users table CHECK constraint to include Board\n";
echo "=====================================================\n\n";

try {
    $pdo = App\DB::pdo();
    echo "✅ Database connection successful\n\n";
    
    // Define current user types (hardcoded to avoid dependency issues)
    $allowedRoles = ['organizer', 'judge', 'contestant', 'emcee', 'tally_master', 'auditor', 'board'];
    
    echo "1. User types to include in constraint:\n";
    foreach ($allowedRoles as $role) {
        echo "   - {$role}\n";
    }
    
    // Check current constraint
    echo "\n2. Checking current CHECK constraint...\n";
    $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
    $createSql = $stmt->fetchColumn();
    
    $currentConstraintRegex = "/CHECK \(role IN \('([^']+)'(?:, '([^']+)')*\)\)/";
    if (preg_match($currentConstraintRegex, $createSql, $matches)) {
        echo "   ✅ CHECK constraint found in users table\n";
    } else {
        echo "   ⚠️ No CHECK constraint found or format is unexpected. Proceeding with recreation.\n";
    }
    
    // Build the new CHECK constraint string
    $newConstraintRoles = "'" . implode("','", $allowedRoles) . "'";
    $newConstraint = "CHECK (role IN ({$newConstraintRoles}))";
    
    // Get existing table info
    $stmt = $pdo->query("PRAGMA table_info(users)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $columnDefinitions = [];
    foreach ($columns as $column) {
        $name = $column['name'];
        $type = $column['type'];
        $notnull = $column['notnull'] ? 'NOT NULL' : 'NULL';
        $dflt_value = $column['dflt_value'] !== null ? "DEFAULT {$column['dflt_value']}" : '';
        $pk = $column['pk'] ? 'PRIMARY KEY' : '';
        
        // Special handling for 'id' column to ensure PRIMARY KEY is set correctly
        if ($name === 'id' && $pk) {
            $columnDefinitions[] = "{$name} {$type} {$notnull} {$pk}";
        } else {
            $columnDefinitions[] = "{$name} {$type} {$notnull} {$dflt_value}";
        }
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Rename old table
        echo "\n3. Creating new users table with updated constraint...\n";
        $pdo->exec("ALTER TABLE users RENAME TO old_users");
        
        // Create new table with updated constraint
        $createTableSql = "
            CREATE TABLE users (
                " . implode(",\n    ", $columnDefinitions) . ",
                {$newConstraint}
            );
        ";
        $pdo->exec($createTableSql);
        echo "   ✅ New users table created\n";
        
        // Copy data from old table to new table
        $columnNames = implode(', ', array_column($columns, 'name'));
        $pdo->exec("INSERT INTO users ({$columnNames}) SELECT {$columnNames} FROM old_users");
        echo "   ✅ Copied " . $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn() . " users to new table\n";
        
        // Drop old table
        $pdo->exec("DROP TABLE old_users");
        echo "   ✅ Old users table dropped\n";
        
        // Recreate indexes (SQLite drops indexes on ALTER TABLE RENAME)
        echo "   ✅ Recreating indexes...\n";
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_email ON users (email);");
        $pdo->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_users_preferred_name ON users (preferred_name);");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_role ON users (role);");
        echo "   ✅ Indexes recreated\n";
        
        $pdo->commit();
        echo "✅ Transaction committed successfully\n";
        
        // 4. Verify new constraint
        echo "\n4. Verifying new constraint...\n";
        $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
        $newCreateSql = $stmt->fetchColumn();
        if (str_contains($newCreateSql, $newConstraint)) {
            echo "   ✅ New constraint verified\n";
        } else {
            echo "   ❌ New constraint NOT found in table definition.\n";
            throw new Exception("Constraint verification failed.");
        }
        
        // 5. Test board user creation
        echo "\n5. Testing board user creation...\n";
        $testUserId = \App\uuid();
        $testSql = "INSERT INTO users (id, name, email, role, created_at) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($testSql);
        $stmt->execute([$testUserId, 'Test Board Member', 'test@example.com', 'board', date('c')]);
        
        echo "   ✅ Test board user created successfully\n";
        
        // Clean up test user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$testUserId]);
        echo "   ✅ Test user cleaned up\n";
        
        echo "\n🎉 CHECK constraint update completed successfully!\n";
        echo "\n📝 Summary:\n";
        echo "   ✅ Updated CHECK constraint to include all user types including Board\n";
        echo "   ✅ Migrated existing users\n";
        echo "   ✅ Verified constraint works with board role\n";
        echo "   ✅ Recreated all indexes\n";
        echo "\n🚀 Board user type can now be created successfully!\n";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    exit(1);
}
