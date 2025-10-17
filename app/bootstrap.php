<?php
declare(strict_types=1);
// Sessions for authentication
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

require __DIR__ . '/lib/helpers.php';
require __DIR__ . '/lib/Router.php';
require __DIR__ . '/lib/DB.php';
require __DIR__ . '/lib/Logger.php';
require __DIR__ . '/lib/Mailer.php';
require __DIR__ . '/routes/controllers.php';

// Session timeout check (configurable)
$timeout = 30 * 60; // Default 30 minutes in seconds
try {
	// Check if system_settings table exists first
	$stmt = App\DB::pdo()->query("SELECT name FROM sqlite_master WHERE type='table' AND name='system_settings'");
	if ($stmt->fetch()) {
		$stmt = App\DB::pdo()->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
		$stmt->execute(['session_timeout']);
		$result = $stmt->fetchColumn();
		if ($result) {
			$timeout = (int)$result;
		}
	}
} catch (\Exception $e) {
	// Use default timeout if settings table doesn't exist or query fails
	error_log('Bootstrap: Could not load session timeout setting: ' . $e->getMessage());
}

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
	// Session expired
	session_unset();
	session_destroy();
	session_start();
	$_SESSION['timeout_message'] = 'Your session has expired. Please log in again.';
}

// Update last activity time
$_SESSION['last_activity'] = time();

App\DB::migrate();


