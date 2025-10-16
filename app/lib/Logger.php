<?php

namespace App;

function uuid(): string { return bin2hex(random_bytes(16)); }

class Logger {
	const LEVEL_DEBUG = 'debug';
	const LEVEL_INFO = 'info';
	const LEVEL_WARN = 'warn';
	const LEVEL_ERROR = 'error';
	
	private static $currentLevel = self::LEVEL_INFO;
	private static $logDirectory = null;
	private static $initialized = false;
	
	public static function setLevel(string $level): void {
		if (in_array($level, [self::LEVEL_DEBUG, self::LEVEL_INFO, self::LEVEL_WARN, self::LEVEL_ERROR])) {
			self::$currentLevel = $level;
		}
	}
	
	public static function initialize(): void {
		if (self::$initialized) {
			return;
		}
		
		try {
			$pdo = DB::pdo();
			$stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
			$stmt->execute(['log_level']);
			$logLevel = $stmt->fetchColumn();
			
			if ($logLevel && in_array($logLevel, [self::LEVEL_DEBUG, self::LEVEL_INFO, self::LEVEL_WARN, self::LEVEL_ERROR])) {
				self::$currentLevel = $logLevel;
			}
		} catch (\Exception $e) {
			// If we can't load the setting, stick with the default
			error_log('Failed to load log level from database: ' . $e->getMessage());
		}
		
		self::$initialized = true;
	}
	
	public static function refreshLevel(): void {
		self::$initialized = false;
		self::initialize();
	}
	
	public static function getLevel(): string {
		return self::$currentLevel;
	}
	
	public static function shouldLog(string $level): bool {
		$levels = [self::LEVEL_DEBUG => 0, self::LEVEL_INFO => 1, self::LEVEL_WARN => 2, self::LEVEL_ERROR => 3];
		return $levels[$level] >= $levels[self::$currentLevel];
	}
	
	private static function getLogDirectory(): string {
		if (self::$logDirectory === null) {
			// Try multiple possible log directories
			$possiblePaths = [
				__DIR__ . '/../logs',
				__DIR__ . '/../../logs',
				'/var/www/html/app/logs',
				'/var/www/html/logs',
				'/var/log/event-manager',
				'/tmp/event-manager-logs'
			];
			
			foreach ($possiblePaths as $path) {
				if (is_dir($path) && is_writable($path)) {
					self::$logDirectory = $path;
					break;
				} elseif (is_writable(dirname($path))) {
					// Try to create the directory
					if (mkdir($path, 0755, true)) {
						self::$logDirectory = $path;
						break;
					}
				}
			}
			
			// Fallback to a temporary directory
			if (self::$logDirectory === null) {
				self::$logDirectory = sys_get_temp_dir() . '/event-manager-logs';
				if (!is_dir(self::$logDirectory)) {
					mkdir(self::$logDirectory, 0755, true);
				}
			}
		}
		
		return self::$logDirectory;
	}
	
	private static function writeToFile(string $level, string $message): void {
		try {
			$logDir = self::getLogDirectory();
			$logFile = $logDir . '/event-manager-' . date('Y-m-d') . '.log';
			
			$timestamp = date('Y-m-d H:i:s');
			$logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
			
			file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
		} catch (\Exception $e) {
			// Silently fail if we can't write to file
			// Don't let logging failures break the application
		}
	}
	
	public static function log(string $action, string $resourceType = null, string $resourceId = null, string $details = null, string $level = self::LEVEL_INFO): void {
		// Initialize Logger if not already done
		self::initialize();
		
		if (!self::shouldLog($level)) {
			return;
		}
		
		$user = $_SESSION['user'] ?? null;
		$userId = $user['id'] ?? null;
		$userName = $user['preferred_name'] ?? $user['name'] ?? 'Unknown';
		$userRole = $user['role'] ?? 'guest';
		$ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
		$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

		// Write to database
		try {
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
		} catch (\Exception $e) {
			// If database logging fails, still try to log to file
			error_log('Database logging failed: ' . $e->getMessage());
		}

		// Write to file
		$logMessage = sprintf(
			'User: %s (%s) | Action: %s | Resource: %s%s | Details: %s | IP: %s',
			$userName,
			$userRole,
			$action,
			$resourceType ?: 'none',
			$resourceId ? " ({$resourceId})" : '',
			$details ?: 'none',
			$ipAddress
		);
		
		self::writeToFile($level, $logMessage);
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
	
	public static function getLogDirectoryPublic(): string {
		return self::getLogDirectory();
	}
	
	public static function getLogFiles(): array {
		$logDir = self::getLogDirectory();
		$files = [];
		
		if (is_dir($logDir)) {
			$files = glob($logDir . '/event-manager-*.log');
			// Sort by modification time, newest first
			usort($files, function($a, $b) {
				return filemtime($b) - filemtime($a);
			});
		}
		
		return $files;
	}
	
	public static function getLogFileContent(string $filename, int $lines = 100): string {
		$logDir = self::getLogDirectory();
		$filePath = $logDir . '/' . basename($filename);
		
		if (!file_exists($filePath) || !is_readable($filePath)) {
			return '';
		}
		
		// Get the last N lines
		$content = '';
		$handle = fopen($filePath, 'r');
		if ($handle) {
			$lineCount = 0;
			$linesArray = [];
			
			// Read all lines
			while (($line = fgets($handle)) !== false) {
				$linesArray[] = $line;
				$lineCount++;
			}
			fclose($handle);
			
			// Get the last N lines
			$startIndex = max(0, $lineCount - $lines);
			$content = implode('', array_slice($linesArray, $startIndex));
		}
		
		return $content;
	}
	
	public static function cleanupOldLogFiles(int $daysToKeep = 30): int {
		$logDir = self::getLogDirectory();
		$files = glob($logDir . '/event-manager-*.log');
		$deletedCount = 0;
		$cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
		
		foreach ($files as $file) {
			if (filemtime($file) < $cutoffTime) {
				if (unlink($file)) {
					$deletedCount++;
				}
			}
		}
		
		return $deletedCount;
	}
}