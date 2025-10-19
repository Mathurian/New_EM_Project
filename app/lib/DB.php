<?php
declare(strict_types=1);
namespace App;
use PDO;
use function App\uuid;

/**
 * Enhanced Database class with PostgreSQL support
 */
class DB {
	private static ?PDO $pdo = null;
	private static ?DatabaseInterface $dbInterface = null;
	private static string $currentType = 'sqlite';
	private static array $config = [];

	public static function initialize(array $config = []): void {
		self::$config = $config;
		self::$currentType = $config['type'] ?? 'sqlite';
		
		// Create database interface
		self::$dbInterface = DatabaseFactory::createFromConfig($config);
		
		// Initialize legacy PDO for backward compatibility
		self::initializeLegacyPDO();
	}

	/**
	 * Initialize legacy PDO for backward compatibility
	 */
	private static function initializeLegacyPDO(): void {
		if (self::$currentType === 'sqlite') {
			self::$pdo = self::createSQLitePDO();
		} else {
			self::$pdo = self::createPostgreSQLPDO();
		}
	}

	/**
	 * Create SQLite PDO connection
	 */
	private static function createSQLitePDO(): PDO {
		$dbDir = dirname(__DIR__) . '/db';
		if (!is_dir($dbDir)) {
			@mkdir($dbDir, 0775, true);
		}
		if (!is_dir($dbDir) || !is_writable($dbDir)) {
			throw new \RuntimeException('Database directory not writable: ' . $dbDir);
		}
		
		$path = $dbDir . '/contest.sqlite';
		$pdo = new PDO('sqlite:' . $path);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		// Configure SQLite for better concurrency and reduced locking
		$pdo->exec('PRAGMA foreign_keys = ON');
		$pdo->exec('PRAGMA journal_mode = WAL');
		$pdo->exec('PRAGMA synchronous = NORMAL');
		$pdo->exec('PRAGMA cache_size = 10000');
		$pdo->exec('PRAGMA temp_store = MEMORY');
		$pdo->exec('PRAGMA busy_timeout = 30000');
		$pdo->exec('PRAGMA wal_autocheckpoint = 1000');
		
		return $pdo;
	}

	/**
	 * Create PostgreSQL PDO connection
	 */
	private static function createPostgreSQLPDO(): PDO {
		$config = self::$config;
		$dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['dbname']}";
		$pdo = new PDO($dsn, $config['username'], $config['password']);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		// PostgreSQL-specific settings
		$pdo->exec('SET timezone = UTC');
		$pdo->exec('SET standard_conforming_strings = on');
		
		return $pdo;
	}

	/**
	 * Get database path (SQLite only)
	 */
	public static function getDatabasePath(): string {
		$dbDir = dirname(__DIR__) . '/db';
		$dbPath = $dbDir . '/contest.sqlite';
		
		// Ensure the db directory exists
		if (!is_dir($dbDir)) {
			mkdir($dbDir, 0755, true);
		}
		
		return $dbPath;
	}
	
	/**
	 * Get PDO instance (legacy compatibility)
	 */
	public static function pdo(): PDO {
		if (!self::$pdo) {
			self::initialize();
		}
		return self::$pdo;
	}

	/**
	 * Get database interface
	 */
	public static function getInterface(): DatabaseInterface {
		if (!self::$dbInterface) {
			self::initialize();
		}
		return self::$dbInterface;
	}

	/**
	 * Get current database type
	 */
	public static function getCurrentType(): string {
		return self::$currentType;
	}

	/**
	 * Check if using PostgreSQL
	 */
	public static function isPostgreSQL(): bool {
		return self::$currentType === 'postgresql';
	}

	/**
	 * Check if using SQLite
	 */
	public static function isSQLite(): bool {
		return self::$currentType === 'sqlite';
	}

	/**
	 * Switch database type
	 */
	public static function switchDatabase(string $type, array $config = []): void {
		self::$currentType = $type;
		self::$config = array_merge(self::$config, $config);
		self::$dbInterface = DatabaseFactory::createFromConfig(self::$config);
		self::initializeLegacyPDO();
	}

	/**
	 * Enhanced query method with database abstraction
	 */
	public static function query(string $sql, array $params = []): array {
		// Convert SQLite-specific queries to PostgreSQL
		if (self::isPostgreSQL()) {
			$sql = self::convertSQLiteToPostgreSQL($sql);
		}
		
		return self::getInterface()->query($sql, $params);
	}

	/**
	 * Enhanced execute method with database abstraction
	 */
	public static function execute(string $sql, array $params = []): bool {
		// Convert SQLite-specific queries to PostgreSQL
		if (self::isPostgreSQL()) {
			$sql = self::convertSQLiteToPostgreSQL($sql);
		}
		
		return self::getInterface()->execute($sql, $params);
	}

	/**
	 * Enhanced fetchOne method
	 */
	public static function fetchOne(string $sql, array $params = []): ?array {
		if (self::isPostgreSQL()) {
			$sql = self::convertSQLiteToPostgreSQL($sql);
		}
		
		return self::getInterface()->fetchOne($sql, $params);
	}

	/**
	 * Enhanced fetchColumn method
	 */
	public static function fetchColumn(string $sql, array $params = []): mixed {
		if (self::isPostgreSQL()) {
			$sql = self::convertSQLiteToPostgreSQL($sql);
		}
		
		return self::getInterface()->fetchColumn($sql, $params);
	}

	/**
	 * Convert SQLite-specific SQL to PostgreSQL
	 */
	private static function convertSQLiteToPostgreSQL(string $sql): string {
		// Convert PRAGMA statements
		if (preg_match('/PRAGMA\s+table_info\(`?(\w+)`?\)/i', $sql, $matches)) {
			$tableName = $matches[1];
			return "
				SELECT 
					column_name as name,
					data_type as type,
					is_nullable as notnull,
					column_default as dflt_value,
					character_maximum_length as length
				FROM information_schema.columns 
				WHERE table_name = '{$tableName}' 
				ORDER BY ordinal_position
			";
		}

		// Convert sqlite_master queries
		if (strpos($sql, 'sqlite_master') !== false) {
			$sql = str_replace('sqlite_master', 'information_schema.tables', $sql);
			$sql = str_replace("type='table'", "table_schema='public'", $sql);
			$sql = str_replace("name NOT LIKE 'sqlite_%'", "table_name NOT LIKE 'pg_%'", $sql);
		}

		// Convert LIMIT/OFFSET syntax (PostgreSQL uses same syntax)
		// Convert string concatenation
		$sql = preg_replace('/(\w+)\s*\|\|\s*(\w+)/', 'CONCAT($1, $2)', $sql);

		// Convert CURRENT_TIMESTAMP
		$sql = str_replace('CURRENT_TIMESTAMP', 'CURRENT_TIMESTAMP', $sql);

		// Convert boolean handling
		$sql = preg_replace('/\bTRUE\b/', 'TRUE', $sql);
		$sql = preg_replace('/\bFALSE\b/', 'FALSE', $sql);

		return $sql;
	}

	/**
	 * Get table information (database-agnostic)
	 */
	public static function getTableInfo(string $tableName): array {
		return self::getInterface()->getTableInfo($tableName);
	}

	/**
	 * Get all tables (database-agnostic)
	 */
	public static function getTables(): array {
		return self::getInterface()->getTables();
	}

	/**
	 * Generate UUID (database-agnostic)
	 */
	public static function generateUUID(): string {
		if (self::isPostgreSQL()) {
			// Use PostgreSQL's native UUID generation
			$result = self::fetchColumn("SELECT uuid_generate_v4()");
			return $result;
		} else {
			// Use custom UUID generation for SQLite
			return uuid();
		}
	}

	/**
	 * Enhanced migrate method with PostgreSQL support
	 */
	public static function migrate(): void {
		if (self::isPostgreSQL()) {
			self::migratePostgreSQL();
		} else {
			self::migrateSQLite();
		}
	}

	/**
	 * Migrate SQLite schema
	 */
	private static function migrateSQLite(): void {
		$sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS contests (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	start_date TEXT NOT NULL,
	end_date TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS categories (
	id TEXT PRIMARY KEY,
	contest_id TEXT NOT NULL,
	name TEXT NOT NULL,
	FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS subcategories (
	id TEXT PRIMARY KEY,
	category_id TEXT NOT NULL,
	name TEXT NOT NULL,
	FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS contestants (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	email TEXT,
	gender TEXT,
	pronouns TEXT,
	contestant_number INTEGER,
	bio TEXT,
	image_path TEXT
);
CREATE TABLE IF NOT EXISTS judges (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	email TEXT,
	gender TEXT,
	pronouns TEXT,
	bio TEXT,
	image_path TEXT,
	is_head_judge INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE IF NOT EXISTS subcategory_contestants (
	subcategory_id TEXT NOT NULL,
	contestant_id TEXT NOT NULL,
	PRIMARY KEY (subcategory_id, contestant_id),
	FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS subcategory_judges (
	subcategory_id TEXT NOT NULL,
	judge_id TEXT NOT NULL,
	PRIMARY KEY (subcategory_id, judge_id),
	FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS criteria (
	id TEXT PRIMARY KEY,
	subcategory_id TEXT NOT NULL,
	name TEXT NOT NULL,
	max_score INTEGER NOT NULL,
	FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS scores (
	id TEXT PRIMARY KEY,
	subcategory_id TEXT NOT NULL,
	contestant_id TEXT NOT NULL,
	judge_id TEXT NOT NULL,
	criterion_id TEXT NOT NULL,
	score REAL NOT NULL,
	created_at TEXT NOT NULL,
	UNIQUE (subcategory_id, contestant_id, judge_id, criterion_id),
	FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
	FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE,
	FOREIGN KEY (criterion_id) REFERENCES criteria(id) ON DELETE CASCADE
);
-- Users for auth
CREATE TABLE IF NOT EXISTS users (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	preferred_name TEXT,
	email TEXT UNIQUE,
	password_hash TEXT,
	role TEXT NOT NULL CHECK (role IN ('organizer','judge','emcee','contestant','tally_master','auditor','board')),
	judge_id TEXT,
	contestant_id TEXT,
	gender TEXT,
	pronouns TEXT,
	session_version INTEGER NOT NULL DEFAULT 1,
	created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE SET NULL,
	FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE SET NULL
);
-- Optional comments per judge per contestant per subcategory
CREATE TABLE IF NOT EXISTS judge_comments (
	id TEXT PRIMARY KEY,
	subcategory_id TEXT NOT NULL,
	contestant_id TEXT NOT NULL,
	judge_id TEXT NOT NULL,
	comment TEXT,
	created_at TEXT NOT NULL,
	UNIQUE (subcategory_id, contestant_id, judge_id),
	FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
	FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE
);
-- Judge certifications to lock edits post-submit
CREATE TABLE IF NOT EXISTS tally_master_certifications (
	id TEXT PRIMARY KEY,
	subcategory_id TEXT NOT NULL,
	signature_name TEXT NOT NULL,
	certified_at TEXT NOT NULL,
	FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE
);
-- Subcategory templates for reuse across categories
CREATE TABLE IF NOT EXISTS subcategory_templates (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	description TEXT,
	subcategory_names TEXT
);
-- Template criteria
CREATE TABLE IF NOT EXISTS template_criteria (
	id TEXT PRIMARY KEY,
	template_id TEXT NOT NULL,
	name TEXT NOT NULL,
	max_score INTEGER NOT NULL,
	FOREIGN KEY (template_id) REFERENCES subcategory_templates(id) ON DELETE CASCADE
);
-- Category-level assignments
CREATE TABLE IF NOT EXISTS category_contestants (
	category_id TEXT NOT NULL,
	contestant_id TEXT NOT NULL,
	PRIMARY KEY (category_id, contestant_id),
	FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
	FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS category_judges (
	category_id TEXT NOT NULL,
	judge_id TEXT NOT NULL,
	PRIMARY KEY (category_id, judge_id),
	FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
	FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE
);
-- Archived contests system
CREATE TABLE IF NOT EXISTS archived_contests (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	description TEXT,
	start_date TEXT,
	end_date TEXT,
	archived_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	archived_by TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS archived_categories (
	id TEXT PRIMARY KEY,
	archived_contest_id TEXT NOT NULL,
	name TEXT NOT NULL,
	description TEXT,
	FOREIGN KEY (archived_contest_id) REFERENCES archived_contests(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS archived_subcategories (
	id TEXT PRIMARY KEY,
	archived_category_id TEXT NOT NULL,
	name TEXT NOT NULL,
	description TEXT,
	score_cap REAL,
	FOREIGN KEY (archived_category_id) REFERENCES archived_categories(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS archived_contestants (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	email TEXT,
	gender TEXT,
	contestant_number INTEGER,
	bio TEXT,
	image_path TEXT
);
CREATE TABLE IF NOT EXISTS archived_judges (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	email TEXT,
	gender TEXT,
	bio TEXT,
	image_path TEXT
);
CREATE TABLE IF NOT EXISTS archived_criteria (
	id TEXT PRIMARY KEY,
	archived_subcategory_id TEXT NOT NULL,
	name TEXT NOT NULL,
	max_score INTEGER NOT NULL,
	FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS archived_scores (
	id TEXT PRIMARY KEY,
	archived_subcategory_id TEXT NOT NULL,
	archived_contestant_id TEXT NOT NULL,
	archived_judge_id TEXT NOT NULL,
	archived_criterion_id TEXT NOT NULL,
	score REAL NOT NULL,
	created_at TEXT NOT NULL,
	FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_contestant_id) REFERENCES archived_contestants(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_judge_id) REFERENCES archived_judges(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_criterion_id) REFERENCES archived_criteria(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS archived_judge_comments (
	id TEXT PRIMARY KEY,
	archived_subcategory_id TEXT NOT NULL,
	archived_contestant_id TEXT NOT NULL,
	archived_judge_id TEXT NOT NULL,
	comment TEXT,
	created_at TEXT NOT NULL,
	FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_contestant_id) REFERENCES archived_contestants(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_judge_id) REFERENCES archived_judges(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS archived_tally_master_certifications (
	id TEXT PRIMARY KEY,
	archived_subcategory_id TEXT NOT NULL,
	signature_name TEXT NOT NULL,
	certified_at TEXT NOT NULL,
	FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE
);
-- Activity logging system
CREATE TABLE IF NOT EXISTS activity_logs (
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
);

-- Overall deductions per contestant per subcategory (admin/head judge only)
CREATE TABLE IF NOT EXISTS overall_deductions (
    id TEXT PRIMARY KEY,
    subcategory_id TEXT NOT NULL,
    contestant_id TEXT NOT NULL,
    amount REAL NOT NULL,
    comment TEXT,
    signature_name TEXT,
    signed_at TEXT,
    created_by TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
    FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
-- System settings
CREATE TABLE IF NOT EXISTS system_settings (
	id TEXT PRIMARY KEY,
	setting_key TEXT UNIQUE NOT NULL,
	setting_value TEXT NOT NULL,
	description TEXT,
	updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_by TEXT,
	FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS backup_logs (
	id TEXT PRIMARY KEY,
	backup_type TEXT NOT NULL CHECK (backup_type IN ('schema', 'full', 'scheduled')),
	file_path TEXT NOT NULL,
	file_size INTEGER NOT NULL,
	status TEXT NOT NULL CHECK (status IN ('success', 'failed', 'in_progress')),
	created_by TEXT,
	created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	error_message TEXT,
	FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);
CREATE TABLE IF NOT EXISTS backup_settings (
	id TEXT PRIMARY KEY,
	backup_type TEXT NOT NULL CHECK (backup_type IN ('schema', 'full')),
	enabled BOOLEAN NOT NULL DEFAULT 0,
	frequency TEXT NOT NULL CHECK (frequency IN ('minutes', 'hours', 'daily', 'weekly', 'monthly')),
	frequency_value INTEGER NOT NULL DEFAULT 1,
	retention_days INTEGER NOT NULL DEFAULT 30,
	last_run TEXT,
	next_run TEXT,
	created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Emcee scripts
CREATE TABLE IF NOT EXISTS emcee_scripts (
	id TEXT PRIMARY KEY,
	filename TEXT NOT NULL,
	file_path TEXT NOT NULL,
	is_active BOOLEAN DEFAULT 1,
	created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Auditor certifications
CREATE TABLE IF NOT EXISTS auditor_certifications (
	id TEXT PRIMARY KEY,
	subcategory_id TEXT NOT NULL,
	signature_name TEXT NOT NULL,
	certified_at TEXT NOT NULL,
	FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE
);

-- Judge score removal requests
CREATE TABLE IF NOT EXISTS judge_score_removal_requests (
	id TEXT PRIMARY KEY,
	subcategory_id TEXT NOT NULL,
	contestant_id TEXT NOT NULL,
	judge_id TEXT NOT NULL,
	reason TEXT NOT NULL,
	requested_by TEXT NOT NULL,
	requested_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	status TEXT NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
	approved_by TEXT,
	approved_at TEXT,
	FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
	FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE,
	FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
	FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);
SQL;
		self::pdo()->exec($sql);

		// Post-creation migrations: add columns if missing
		self::addColumnIfMissing('contestants', 'gender', 'TEXT');
		self::addColumnIfMissing('contestants', 'pronouns', 'TEXT');
		self::addColumnIfMissing('judges', 'gender', 'TEXT');
		self::addColumnIfMissing('judges', 'pronouns', 'TEXT');
		self::addColumnIfMissing('users', 'gender', 'TEXT');
		self::addColumnIfMissing('users', 'pronouns', 'TEXT');
		
		// Add description and score_cap columns to subcategories
		self::addColumnIfMissing('subcategories', 'description', 'TEXT');
		self::addColumnIfMissing('subcategories', 'score_cap', 'REAL');
		self::addColumnIfMissing('subcategory_templates', 'subcategory_names', 'TEXT');
		self::addColumnIfMissing('subcategory_templates', 'max_score', 'INTEGER DEFAULT 60');
		self::addColumnIfMissing('contestants', 'contestant_number', 'INTEGER');
		self::addColumnIfMissing('contestants', 'bio', 'TEXT');
		self::addColumnIfMissing('contestants', 'image_path', 'TEXT');
		self::addColumnIfMissing('judges', 'bio', 'TEXT');
		self::addColumnIfMissing('judges', 'image_path', 'TEXT');
		self::addColumnIfMissing('judges', 'is_head_judge', 'INTEGER NOT NULL DEFAULT 0');
		self::addColumnIfMissing('users', 'preferred_name', 'TEXT');
		self::addColumnIfMissing('users', 'pronouns', 'TEXT');
		self::addColumnIfMissing('users', 'contestant_id', 'TEXT');
		self::addColumnIfMissing('users', 'created_at', 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP');
	}

	/**
	 * Migrate PostgreSQL schema
	 */
	private static function migratePostgreSQL(): void {
		// Enable UUID extension
		self::execute("CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\"");
		
		// Use SchemaMigrator for PostgreSQL schema creation
		$schemaMigrator = new SchemaMigrator(
			DatabaseFactory::createSQLite(self::getDatabasePath()),
			self::getInterface()
		);
		
		$schemaMigrator->migrateSchema();
	}

	/**
	 * Add column if missing (SQLite only)
	 */
	private static function addColumnIfMissing(string $table, string $column, string $definition): void {
		if (self::isSQLite()) {
			try {
				$columns = self::pdo()->query("PRAGMA table_info(`{$table}`)")->fetchAll(\PDO::FETCH_ASSOC);
				$columnExists = false;
				foreach ($columns as $col) {
					if ($col['name'] === $column) {
						$columnExists = true;
						break;
					}
				}
				
				if (!$columnExists) {
					self::pdo()->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
				}
			} catch (\Exception $e) {
				// Column might already exist or table might not exist
				error_log("Failed to add column {$column} to {$table}: " . $e->getMessage());
			}
		}
	}

	/**
	 * Execute with retry and error handling
	 */
	public static function executeWithRetry(callable $operation, int $maxRetries = 3): mixed {
		$attempt = 0;
		$lastException = null;
		
		while ($attempt < $maxRetries) {
			try {
				return $operation();
			} catch (\PDOException $e) {
				$lastException = $e;
				
				// Check if it's a locking error
				if (strpos($e->getMessage(), 'database is locked') !== false || 
					strpos($e->getMessage(), 'database table is locked') !== false) {
					
					$attempt++;
					if ($attempt < $maxRetries) {
						// Wait before retrying (exponential backoff)
						usleep(pow(2, $attempt) * 100000); // 0.1s, 0.2s, 0.4s
						continue;
					}
				}
				
				// If it's not a locking error or we've exhausted retries, throw the exception
				throw $e;
			}
		}
		
		throw $lastException;
	}

	/**
	 * Execute a database operation with automatic retry and error handling
	 */
	public static function safeExecute(callable $operation, string $context = ''): mixed {
		try {
			return self::executeWithRetry($operation);
		} catch (\PDOException $e) {
			// For readonly database errors, silently fail for non-critical operations
			if (strpos($e->getMessage(), 'readonly database') !== false || 
				strpos($e->getMessage(), 'permission denied') !== false) {
				// Return null for non-critical operations like logging
				return null;
			}
			
			// For other database errors, log and potentially rethrow
			error_log("Database error in {$context}: " . $e->getMessage());
			
			// For critical operations, we might want to rethrow
			if (strpos($e->getMessage(), 'database is locked') !== false) {
				// Return a safe default or null for non-critical operations
				return null;
			}
			
			throw $e;
		}
	}

	/**
	 * Check database health and optimize if needed
	 */
	public static function optimizeDatabase(): void {
		if (self::isSQLite()) {
			// SQLite optimization
			self::pdo()->exec('PRAGMA optimize');
			self::pdo()->exec('PRAGMA vacuum');
		} else {
			// PostgreSQL optimization
			self::execute('VACUUM ANALYZE');
		}
	}
}