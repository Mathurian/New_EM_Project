<?php
/**
 * Check Auditor User Type in Database
 * Verifies if Auditor users exist and shows current state
 */

require_once __DIR__ . '/app/lib/DB.php';

echo "Auditor User Type Database Check\n";
echo "================================\n\n";

try {
    $pdo = App\DB::pdo();
    echo "✅ Database connection successful\n\n";
    
    // Check if auditor_certifications table exists
    echo "1. Checking auditor_certifications table...\n";
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='auditor_certifications'")->fetchAll();
    
    if (empty($tables)) {
        echo "❌ auditor_certifications table does not exist\n";
        echo "   Run: php migrate_auditor_certifications.php\n\n";
    } else {
        echo "✅ auditor_certifications table exists\n\n";
    }
    
    // Check for Auditor users
    echo "2. Checking for Auditor users...\n";
    $auditorUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'auditor'")->fetchColumn();
    echo "   Found $auditorUsers Auditor user(s)\n";
    
    if ($auditorUsers > 0) {
        echo "\n   Auditor users:\n";
        $auditors = $pdo->query("SELECT id, name, email, created_at FROM users WHERE role = 'auditor'")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($auditors as $auditor) {
            echo "   - {$auditor['name']} ({$auditor['email']}) - Created: {$auditor['created_at']}\n";
        }
    } else {
        echo "   No Auditor users found\n";
    }
    
    // Check all user roles in database
    echo "\n3. All user roles in database:\n";
    $roles = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role ORDER BY role")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($roles as $role) {
        echo "   {$role['role']}: {$role['count']} user(s)\n";
    }
    
    // Check if Auditor role is in validation rules
    echo "\n4. Checking validation rules...\n";
    $allowedRoles = ['organizer', 'judge', 'contestant', 'emcee', 'tally_master', 'auditor'];
    echo "   Expected roles: " . implode(', ', $allowedRoles) . "\n";
    
    $dbRoles = array_column($roles, 'role');
    $missingRoles = array_diff($allowedRoles, $dbRoles);
    $extraRoles = array_diff($dbRoles, $allowedRoles);
    
    if (empty($missingRoles) && empty($extraRoles)) {
        echo "   ✅ All expected roles are present\n";
    } else {
        if (!empty($missingRoles)) {
            echo "   ⚠️  Missing roles: " . implode(', ', $missingRoles) . "\n";
        }
        if (!empty($extraRoles)) {
            echo "   ⚠️  Extra roles: " . implode(', ', $extraRoles) . "\n";
        }
    }
    
    // Check auditor certifications
    echo "\n5. Checking auditor certifications...\n";
    if (!empty($tables)) {
        $certifications = $pdo->query("SELECT COUNT(*) FROM auditor_certifications")->fetchColumn();
        echo "   Found $certifications auditor certification(s)\n";
        
        if ($certifications > 0) {
            $certs = $pdo->query("
                SELECT ac.*, u.name as auditor_name 
                FROM auditor_certifications ac 
                JOIN users u ON ac.auditor_id = u.id
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            echo "\n   Certifications:\n";
            foreach ($certs as $cert) {
                $status = $cert['certified_at'] ? "Certified: {$cert['certified_at']}" : "Not certified";
                echo "   - {$cert['auditor_name']}: $status\n";
            }
        }
    } else {
        echo "   Cannot check certifications (table doesn't exist)\n";
    }
    
    echo "\n🎯 Database check completed!\n";
    
    if ($auditorUsers === 0) {
        echo "\n📝 To create an Auditor user:\n";
        echo "1. Run: php migrate_auditor_certifications.php (if table doesn't exist)\n";
        echo "2. Go to /users/new in the web interface\n";
        echo "3. Select 'Auditor' as the role\n";
        echo "4. Provide a password (required)\n";
        echo "5. Fill in other required fields\n";
        echo "6. Submit the form\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database check failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
