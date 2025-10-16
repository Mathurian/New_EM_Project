<?php

namespace App;

function uuid(): string { return bin2hex(random_bytes(16)); }

class Logger {
	const LEVEL_DEBUG = 'debug';
	const LEVEL_INFO = 'info';
	const LEVEL_WARN = 'warn';
	const LEVEL_ERROR = 'error';
	
	private static $currentLevel = self::LEVEL_INFO;
	
	public static function setLevel(string $level): void {
		if (in_array($level, [self::LEVEL_DEBUG, self::LEVEL_INFO, self::LEVEL_WARN, self::LEVEL_ERROR])) {
			self::$currentLevel = $level;
		}
	}
	
	public static function getLevel(): string {
		return self::$currentLevel;
	}
	
	public static function shouldLog(string $level): bool {
		$levels = [self::LEVEL_DEBUG => 0, self::LEVEL_INFO => 1, self::LEVEL_WARN => 2, self::LEVEL_ERROR => 3];
		return $levels[$level] >= $levels[self::$currentLevel];
	}
	
	public static function log(string $action, string $resourceType = null, string $resourceId = null, string $details = null, string $level = self::LEVEL_INFO): void {
		if (!self::shouldLog($level)) {
			return;
		}
		
		$user = $_SESSION['user'] ?? null;
		$userId = $user['id'] ?? null;
		$userName = $user['preferred_name'] ?? $user['name'] ?? 'Unknown';
		$userRole = $user['role'] ?? 'guest';
		$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

		$stmt = DB::pdo()->prepare('
			INSERT INTO activity_logs (id, user_id, user_name, user_role, action, resource_type, resource_id, details, ip_address, user_agent, log_level, created_at)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		');
		$stmt->execute([
			uuid(),
			$userId,
			$userName,
			$userRole,
			$action,
			$resourceType,
			$resourceId,
			$details,
			$ipAddress,
			$userAgent,
			$level,
			date('c')
		]);
	}

	public static function debug(string $action, string $resourceType = null, string $resourceId = null, string $details = null): void {
		self::log($action, $resourceType, $resourceId, $details, self::LEVEL_DEBUG);
	}

	public static function info(string $action, string $resourceType = null, string $resourceId = null, string $details = null): void {
		self::log($action, $resourceType, $resourceId, $details, self::LEVEL_INFO);
	}

	public static function warn(string $action, string $resourceType = null, string $resourceId = null, string $details = null): void {
		self::log($action, $resourceType, $resourceId, $details, self::LEVEL_WARN);
	}

	public static function error(string $action, string $resourceType = null, string $resourceId = null, string $details = null): void {
		self::log($action, $resourceType, $resourceId, $details, self::LEVEL_ERROR);
	}
	
	public static function logLogin(string $emailOrName, bool $success): void {
		$action = $success ? 'login_success' : 'login_failed';
		$level = $success ? self::LEVEL_INFO : self::LEVEL_WARN;
		self::log($action, 'user', null, 'Email/Name: ' . $emailOrName, $level);
	}
	
	public static function logLogout(): void {
		self::log('logout', 'user', null, null, self::LEVEL_INFO);
	}

	public static function logSessionTimeout(?string $userId, ?string $name, ?string $role): void {
		// Temporarily spoof session user context for this log
		$prev = $_SESSION['user'] ?? null;
		$_SESSION['user'] = ['id' => $userId, 'name' => $name, 'preferred_name' => $name, 'role' => $role];
		self::log('session_timeout', 'user', $userId ?? '', 'Session timed out', self::LEVEL_WARN);
		if ($prev !== null) { $_SESSION['user'] = $prev; } else { unset($_SESSION['user']); }
	}
	
	public static function logScoreSubmission(string $subcategoryId, string $contestantId, string $judgeId, int $scoreCount): void {
		self::log(
			'score_submission',
			'subcategory',
			$subcategoryId,
			"Contestant: $contestantId, Judge: $judgeId, Scores: $scoreCount",
			self::LEVEL_INFO
		);
	}
	
	public static function logScoreCertification(string $subcategoryId, string $judgeId): void {
		self::log(
			'score_certification',
			'subcategory',
			$subcategoryId,
			"Judge: $judgeId",
			self::LEVEL_INFO
		);
	}
	
	public static function logUserCreation(string $userId, string $role, string $name): void {
		self::log(
			'user_creation',
			'user',
			$userId,
			"Role: $role, Name: $name",
			self::LEVEL_INFO
		);
	}
	
	public static function logUserDeletion(string $userId, string $role, string $name): void {
		self::log(
			'user_deletion',
			'user',
			$userId,
			"Role: $role, Name: $name",
			self::LEVEL_WARN
		);
	}
	
	public static function logContestArchive(string $contestId, string $contestName): void {
		self::log(
			'contest_archive',
			'contest',
			$contestId,
			"Contest: $contestName",
			self::LEVEL_INFO
		);
	}
	
	public static function logAdminAction(string $action, string $resourceType = null, string $resourceId = null, string $details = null): void {
		self::log(
			'admin_' . $action,
			$resourceType,
			$resourceId,
			$details,
			self::LEVEL_INFO
		);
	}
	
	public static function logBulkOperation(string $operation, string $resourceType, int $count): void {
		self::log(
			'bulk_' . $operation,
			$resourceType,
			null,
			"Count: $count",
			self::LEVEL_INFO
		);
	}

	public static function logSystemError(string $error, string $context = null): void {
		self::log('system_error', 'system', null, $context ? "Error: {$error} in context: {$context}" : "Error: {$error}", self::LEVEL_ERROR);
	}

	public static function logSecurityEvent(string $event, string $details = null): void {
		self::log('security_event', 'security', null, $details ?: $event, self::LEVEL_WARN);
	}

	public static function logDataAccess(string $resourceType, string $resourceId, string $action): void {
		self::log('data_access', $resourceType, $resourceId, "Accessed {$resourceType} {$resourceId} for {$action}", self::LEVEL_DEBUG);
	}
}