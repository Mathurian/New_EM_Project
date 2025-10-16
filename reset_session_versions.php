<?php
/**
 * Reset Session Versions Script
 * This script resets all user session versions to fix login issues
 */

require_once __DIR__ . '/app/bootstrap.php';

echo "=== Reset Session Versions ===\n";

try {
    $pdo = App\DB::pdo();
    
    // Get all users
    $stmt = $pdo->query("SELECT id, email, preferred_name, role, session_version FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($users) . " users\n";
    
    $resetCount = 0;
    
    foreach ($users as $user) {
        // Generate a new session version
        $newSessionVersion = App\DB::uuid();
        
        // Update the user's session version
        $stmt = $pdo->prepare("UPDATE users SET session_version = ? WHERE id = ?");
        $stmt->execute([$newSessionVersion, $user['id']]);
        
        echo "Reset session version for user: {$user['email']} ({$user['preferred_name']}) - Role: {$user['role']}\n";
        echo "  Old version: {$user['session_version']}\n";
        echo "  New version: {$newSessionVersion}\n";
        
        $resetCount++;
    }
    
    echo "\n=== Reset Complete ===\n";
    echo "Reset session versions for {$resetCount} users\n";
    echo "All users should now be able to log in normally\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
