<?php
declare(strict_types=1);
namespace App;
use PDO;

class DB {
	private static ?PDO $pdo = null;

	public static function pdo(): PDO {
		if (!self::$pdo) {
			// Build absolute path to DB directory and file
			$dbDir = dirname(__DIR__) . '/db';
			if (!is_dir($dbDir)) {
				// Attempt to create the directory (web server user must have rights)
				@mkdir($dbDir, 0775, true);
			}
			if (!is_dir($dbDir) || !is_writable($dbDir)) {
				throw new \RuntimeException('Database directory not writable: ' . $dbDir);
			}
			$path = $dbDir . '/contest.sqlite';
			self::$pdo = new PDO('sqlite:' . $path);
			self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			self::$pdo->exec('PRAGMA foreign_keys = ON');
		}
		return self::$pdo;
	}
	
	private static function uuid(): string {
		return bin2hex(random_bytes(16));
	}
	
	public static function migrate(): void {
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
	contestant_number INTEGER,
	bio TEXT,
	image_path TEXT
);
CREATE TABLE IF NOT EXISTS judges (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	email TEXT,
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
	role TEXT NOT NULL CHECK (role IN ('organizer','judge','emcee','contestant')),
	judge_id TEXT,
	gender TEXT,
	session_version INTEGER NOT NULL DEFAULT 1,
	FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE SET NULL
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
CREATE TABLE IF NOT EXISTS judge_certifications (
	id TEXT PRIMARY KEY,
	subcategory_id TEXT NOT NULL,
	contestant_id TEXT NOT NULL,
	judge_id TEXT NOT NULL,
	signature_name TEXT NOT NULL,
	certified_at TEXT NOT NULL,
	UNIQUE (subcategory_id, contestant_id, judge_id),
	FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
	FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE
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
	score INTEGER NOT NULL,
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
	comment TEXT NOT NULL,
	FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_contestant_id) REFERENCES archived_contestants(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_judge_id) REFERENCES archived_judges(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS archived_judge_certifications (
	id TEXT PRIMARY KEY,
	archived_subcategory_id TEXT NOT NULL,
	archived_judge_id TEXT NOT NULL,
	signature_name TEXT NOT NULL,
	certified_at TEXT NOT NULL,
	UNIQUE (archived_subcategory_id, archived_judge_id),
	FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_judge_id) REFERENCES archived_judges(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS archived_subcategory_contestants (
	archived_subcategory_id TEXT NOT NULL,
	archived_contestant_id TEXT NOT NULL,
	PRIMARY KEY (archived_subcategory_id, archived_contestant_id),
	FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_contestant_id) REFERENCES archived_contestants(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS archived_subcategory_judges (
	archived_subcategory_id TEXT NOT NULL,
	archived_judge_id TEXT NOT NULL,
	PRIMARY KEY (archived_subcategory_id, archived_judge_id),
	FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_judge_id) REFERENCES archived_judges(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS archived_category_contestants (
	archived_category_id TEXT NOT NULL,
	archived_contestant_id TEXT NOT NULL,
	PRIMARY KEY (archived_category_id, archived_contestant_id),
	FOREIGN KEY (archived_category_id) REFERENCES archived_categories(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_contestant_id) REFERENCES archived_contestants(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS archived_category_judges (
	archived_category_id TEXT NOT NULL,
	archived_judge_id TEXT NOT NULL,
	PRIMARY KEY (archived_category_id, archived_judge_id),
	FOREIGN KEY (archived_category_id) REFERENCES archived_categories(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_judge_id) REFERENCES archived_judges(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS archived_overall_deductions (
	id TEXT PRIMARY KEY,
	archived_subcategory_id TEXT NOT NULL,
	archived_contestant_id TEXT NOT NULL,
	amount REAL NOT NULL,
	comment TEXT NOT NULL,
	created_by TEXT NOT NULL,
	created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	signature_name TEXT NOT NULL,
	signed_at TEXT NOT NULL,
	FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_contestant_id) REFERENCES archived_contestants(id) ON DELETE CASCADE
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
	filepath TEXT NOT NULL,
	is_active BOOLEAN DEFAULT 1,
	created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (id) REFERENCES users(id) ON DELETE CASCADE
);
SQL;
		self::pdo()->exec($sql);

		// Post-creation migrations: add gender columns if missing
		self::addColumnIfMissing('contestants', 'gender', 'TEXT');
		self::addColumnIfMissing('judges', 'gender', 'TEXT');
		self::addColumnIfMissing('users', 'gender', 'TEXT');
		
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
		self::addColumnIfMissing('users', 'session_version', 'INTEGER NOT NULL DEFAULT 1');
		self::addColumnIfMissing('activity_logs', 'log_level', 'TEXT DEFAULT "info"');
		// overall_deductions new fields
		self::addColumnIfMissing('overall_deductions', 'signature_name', 'TEXT');
		self::addColumnIfMissing('overall_deductions', 'signed_at', 'TEXT');
		
		// users table new fields
		self::addColumnIfMissing('users', 'contestant_id', 'TEXT');
		
		// emcee_scripts new fields
		self::addColumnIfMissing('emcee_scripts', 'created_at', 'TEXT');
		self::addColumnIfMissing('emcee_scripts', 'uploaded_by', 'TEXT');
		self::addColumnIfMissing('emcee_scripts', 'title', 'TEXT');
		self::addColumnIfMissing('emcee_scripts', 'description', 'TEXT');
		self::addColumnIfMissing('emcee_scripts', 'file_name', 'TEXT');
		self::addColumnIfMissing('emcee_scripts', 'file_size', 'INTEGER');
		self::addColumnIfMissing('emcee_scripts', 'uploaded_at', 'TEXT');
		
		// Migrate judge_certifications to include contestant_id
		self::migrateJudgeCertifications();
		
		// Update role constraint to include emcee
		self::updateRoleConstraint();

		// Seed a default organizer if none exist
		self::seedDefaultAdmin();
		self::seedDefaultSettings();
	}
	
	private static function seedDefaultSettings(): void {
		$pdo = self::pdo();
		
		// Check if system settings already exist
		$stmt = $pdo->query('SELECT COUNT(*) FROM system_settings');
		if ($stmt->fetchColumn() == 0) {
			// Insert default session timeout (30 minutes)
			$stmt = $pdo->prepare('INSERT INTO system_settings (id, setting_key, setting_value, description) VALUES (?, ?, ?, ?)');
			$stmt->execute([uuid(), 'session_timeout', '1800', 'Session timeout in seconds (default: 30 minutes)']);
			
			// Insert default log level
			$stmt = $pdo->prepare('INSERT INTO system_settings (id, setting_key, setting_value, description) VALUES (?, ?, ?, ?)');
			$stmt->execute([uuid(), 'log_level', 'info', 'Logging level: debug, info, warn, error (default: info)']);
		}
		
		// Check if backup settings already exist
		$stmt = $pdo->query('SELECT COUNT(*) FROM backup_settings');
		if ($stmt->fetchColumn() == 0) {
			// Seed default backup settings
			$stmt = $pdo->prepare('INSERT INTO backup_settings (id, backup_type, enabled, frequency, frequency_value, retention_days) VALUES (?, ?, ?, ?, ?, ?)');
			$stmt->execute([uuid(), 'schema', 0, 'daily', 1, 30]);
			$stmt->execute([uuid(), 'full', 0, 'weekly', 1, 30]);
		} else {
			// Migrate existing backup settings to include frequency_value
			self::addColumnIfMissing('backup_settings', 'frequency_value', 'INTEGER NOT NULL DEFAULT 1');
		}
	}

	private static function updateRoleConstraint(): void {
		$pdo = self::pdo();
		
		// Check if the constraint needs updating by trying to insert a test emcee role
		try {
			$pdo->exec("INSERT INTO users (id, name, email, password_hash, role) VALUES ('test_constraint', 'test', 'test@test.com', 'test', 'emcee')");
			$pdo->exec("DELETE FROM users WHERE id = 'test_constraint'");
			// If we get here, the constraint already allows emcee
			return;
		} catch (\PDOException $e) {
			// Constraint doesn't allow emcee, need to recreate table
			error_log('Role constraint update needed: ' . $e->getMessage());
		}
		
		try {
			// Clean up any existing users_new table from previous failed attempts
			$pdo->exec('DROP TABLE IF EXISTS users_new');
			
			// Create new users table with updated constraint
			$pdo->exec('CREATE TABLE users_new (
				id TEXT PRIMARY KEY,
				name TEXT NOT NULL,
				preferred_name TEXT,
				email TEXT UNIQUE,
				password_hash TEXT,
				role TEXT NOT NULL CHECK (role IN (\'organizer\',\'judge\',\'emcee\',\'contestant\')),
				judge_id TEXT,
				gender TEXT,
				FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE SET NULL
			)');
			
			// Copy data from old table with explicit column mapping
			$pdo->exec('INSERT INTO users_new (id, name, preferred_name, email, password_hash, role, judge_id, gender) 
						SELECT id, name, preferred_name, email, password_hash, role, judge_id, gender FROM users');
			
			// Drop old table and rename new one
			$pdo->exec('DROP TABLE users');
			$pdo->exec('ALTER TABLE users_new RENAME TO users');
			
			error_log('Role constraint updated successfully');
		} catch (\PDOException $e) {
			error_log('Failed to update role constraint: ' . $e->getMessage());
			// Try to clean up and restore if possible
			try {
				$pdo->exec('DROP TABLE IF EXISTS users_new');
			} catch (\PDOException $cleanupError) {
				error_log('Failed to cleanup users_new table: ' . $cleanupError->getMessage());
			}
			throw $e;
		}
	}

	private static function addColumnIfMissing(string $table, string $column, string $type): void {
		$pdo = self::pdo();
		$exists = false;
		$stmt = $pdo->prepare('PRAGMA table_info(' . $table . ')');
		$stmt->execute();
		$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach ($cols as $col) {
			if (($col['name'] ?? '') === $column) { $exists = true; break; }
		}
		if (!$exists) {
			$pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $type);
		}
	}

	private static function migrateJudgeCertifications(): void {
		$pdo = self::pdo();
		
		// Check if contestant_id column exists
		$stmt = $pdo->prepare('PRAGMA table_info(judge_certifications)');
		$stmt->execute();
		$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$hasContestantId = false;
		foreach ($cols as $col) {
			if ($col['name'] === 'contestant_id') {
				$hasContestantId = true;
				break;
			}
		}
		
		if (!$hasContestantId) {
			// Add contestant_id column
			$pdo->exec('ALTER TABLE judge_certifications ADD COLUMN contestant_id TEXT');
			
			// For existing certifications, we need to create one per contestant in the subcategory
			$stmt = $pdo->prepare('SELECT * FROM judge_certifications WHERE contestant_id IS NULL');
			$stmt->execute();
			$oldCertifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			foreach ($oldCertifications as $cert) {
				// Get all contestants for this subcategory
				$stmt = $pdo->prepare('SELECT contestant_id FROM subcategory_contestants WHERE subcategory_id = ?');
				$stmt->execute([$cert['subcategory_id']]);
				$contestants = $stmt->fetchAll(PDO::FETCH_COLUMN);
				
				// Create a certification for each contestant
				foreach ($contestants as $contestantId) {
					$stmt = $pdo->prepare('INSERT INTO judge_certifications (id, subcategory_id, contestant_id, judge_id, signature_name, certified_at) VALUES (?, ?, ?, ?, ?, ?)');
					$stmt->execute([uuid(), $cert['subcategory_id'], $contestantId, $cert['judge_id'], $cert['signature_name'], $cert['certified_at']]);
				}
				
				// Delete the old certification
				$stmt = $pdo->prepare('DELETE FROM judge_certifications WHERE id = ?');
				$stmt->execute([$cert['id']]);
			}
			
			// Update the unique constraint
			$pdo->exec('DROP INDEX IF EXISTS sqlite_autoindex_judge_certifications_1');
			$pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS judge_certifications_unique ON judge_certifications (subcategory_id, contestant_id, judge_id)');
		}
	}

	private static function seedDefaultAdmin(): void {
		$pdo = self::pdo();
		$count = (int)$pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='users'")->fetchColumn();
		if ($count === 0) { return; }
		$hasOrganizer = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='organizer'")->fetchColumn();
		if ($hasOrganizer > 0) { return; }
		$defaultEmail = 'admin@example.com';
		$defaultName = 'Admin';
		$defaultGender = null;
		$defaultPassword = 'ChangeMe123!';
		$hash = password_hash($defaultPassword, PASSWORD_BCRYPT);
		$pdo->prepare('INSERT INTO users (id,name,email,password_hash,role,gender) VALUES (?,?,?,?,?,?)')
			->execute([bin2hex(random_bytes(16)), $defaultName, $defaultEmail, $hash, 'organizer', $defaultGender]);
	}
}


