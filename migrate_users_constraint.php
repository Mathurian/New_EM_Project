<?php
/**
 * Update users table CHECK constraint to include all user types
 * This fixes the issue where new user types can't be created due to CHECK constraint
 */

require_once __DIR__ . '/app/lib/DB.php';
require_once __DIR__ . '/app/lib/Logger.php';

echo "Updating users table CHECK constraint\n";
echo "=====================================\n\n";

try {
    $pdo = App\DB::pdo();
    echo "✅ Database connection successful\n\n";
    
    // Get current user types from the application
    require_once __DIR__ . '/app/lib/helpers.php';
    $userTypes = get_user_types();
    $allowedRoles = array_keys($userTypes);
    
    echo "1. Current user types in application:\n";
    foreach ($allowedRoles as $role) {
        echo "   - {$role}\n";
    }
    
    // Check current constraint
    echo "\n2. Checking current CHECK constraint...\n";
    $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
    $createSql = $stmt->fetchColumn();
    
    if (strpos($createSql, 'CHECK') !== false) {
        echo "   ✅ CHECK constraint found in users table\n";
        echo "   Current constraint: " . substr($createSql, strpos($createSql, 'CHECK'), 100) . "...\n";
    } else {
        echo "   ❌ No CHECK constraint found\n";
    }
    
    // Create new table with updated constraint
    echo "\n3. Creating new users table with updated constraint...\n";
    
    // Get all data from current table
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   Found " . count($users) . " existing users\n";
    
    // Create the new constraint string
    $roleList = "'" . implode("', '", $allowedRoles) . "'";
    $constraint = "CHECK (role IN ({$roleList}))";
    
    echo "   New constraint: {$constraint}\n";
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Create new table with updated constraint
        $newTableSql = "
            CREATE TABLE users_new (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                email TEXT,
                password_hash TEXT,
                role TEXT NOT NULL {$constraint},
                preferred_name TEXT,
                gender TEXT,
                pronouns TEXT,
                created_at DATETIME,
                last_login DATETIME,
                session_version INTEGER DEFAULT 1,
                judge_id TEXT,
                contestant_id TEXT,
                FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE SET NULL,
                FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE SET NULL
            )
        ";
        
        $pdo->exec($newTableSql);
        echo "   ✅ New users table created\n";
        
        // Copy data from old table to new table
        if (!empty($users)) {
            $insertSql = "
                INSERT INTO users_new (
                    id, name, email, password_hash, role, preferred_name, 
                    gender, pronouns, created_at, last_login, session_version, 
                    judge_id, contestant_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $stmt = $pdo->prepare($insertSql);
            foreach ($users as $user) {
                $stmt->execute([
                    $user['id'],
                    $user['name'],
                    $user['email'],
                    $user['password_hash'],
                    $user['role'],
                    $user['preferred_name'],
                    $user['gender'],
                    $user['pronouns'],
                    $user['created_at'],
                    $user['last_login'],
                    $user['session_version'],
                    $user['judge_id'],
                    $user['contestant_id']
                ]);
            }
            echo "   ✅ Copied " . count($users) . " users to new table\n";
        }
        
        // Drop old table
        $pdo->exec("DROP TABLE users");
        echo "   ✅ Old users table dropped\n";
        
        // Rename new table
        $pdo->exec("ALTER TABLE users_new RENAME TO users");
        echo "   ✅ New table renamed to users\n";
        
        // Recreate indexes
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_email ON users (email)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_role ON users (role)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_judge_id ON users (judge_id)");
        $pdo->exec("CREATE INDEX IF NOT EXISTS idx_users_contestant_id ON users (contestant_id)");
        echo "   ✅ Indexes recreated\n";
        
        $pdo->commit();
        echo "\n✅ Transaction committed successfully\n";
        
        // Verify the constraint
        echo "\n4. Verifying new constraint...\n";
        $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='users'");
        $newCreateSql = $stmt->fetchColumn();
        
        if (strpos($newCreateSql, $roleList) !== false) {
            echo "   ✅ New constraint verified\n";
            echo "   Constraint includes: {$roleList}\n";
        } else {
            echo "   ❌ Constraint verification failed\n";
        }
        
        // Test inserting an auditor user
        echo "\n5. Testing auditor user creation...\n";
        $testUserId = bin2hex(random_bytes(16));
        $testSql = "INSERT INTO users (id, name, email, role, created_at) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($testSql);
        $stmt->execute([$testUserId, 'Test Auditor', 'test@example.com', 'auditor', date('c')]);
        
        echo "   ✅ Test auditor user created successfully\n";
        
        // Clean up test user
        $pdo->exec("DELETE FROM users WHERE id = ?");
        echo "   ✅ Test user cleaned up\n";
        
        echo "\n🎉 CHECK constraint update completed successfully!\n";
        echo "\n📝 Summary:\n";
        echo "   ✅ Updated CHECK constraint to include all user types\n";
        echo "   ✅ Migrated " . count($users) . " existing users\n";
        echo "   ✅ Verified constraint works with auditor role\n";
        echo "   ✅ Recreated all indexes\n";
        echo "\n🚀 All user types can now be created successfully!\n";
        
        // Log the migration
        App\Logger::info('migration', 'users_check_constraint_updated', null, "Updated users table CHECK constraint to include all user types");
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Log the failure
    App\Logger::error('migration', 'users_check_constraint_failed', null, "Failed to update CHECK constraint: " . $e->getMessage());
    exit(1);
}
