-- Database Schema Export
-- Generated on: 2025-10-18 05:00:01

CREATE TABLE activity_logs (
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
	created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, log_level TEXT DEFAULT "info",
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE archived_categories (
	id TEXT PRIMARY KEY,
	archived_contest_id TEXT NOT NULL,
	name TEXT NOT NULL,
	description TEXT,
	FOREIGN KEY (archived_contest_id) REFERENCES archived_contests(id) ON DELETE CASCADE
);

CREATE TABLE archived_category_contestants (
	archived_category_id TEXT NOT NULL,
	archived_contestant_id TEXT NOT NULL,
	PRIMARY KEY (archived_category_id, archived_contestant_id),
	FOREIGN KEY (archived_category_id) REFERENCES archived_categories(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_contestant_id) REFERENCES archived_contestants(id) ON DELETE CASCADE
);

CREATE TABLE archived_category_judges (
	archived_category_id TEXT NOT NULL,
	archived_judge_id TEXT NOT NULL,
	PRIMARY KEY (archived_category_id, archived_judge_id),
	FOREIGN KEY (archived_category_id) REFERENCES archived_categories(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_judge_id) REFERENCES archived_judges(id) ON DELETE CASCADE
);

CREATE TABLE archived_contestants (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	email TEXT,
	gender TEXT,
	contestant_number INTEGER,
	bio TEXT,
	image_path TEXT
);

CREATE TABLE archived_contests (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	description TEXT,
	start_date TEXT,
	end_date TEXT,
	archived_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	archived_by TEXT NOT NULL
);

CREATE TABLE archived_criteria (
	id TEXT PRIMARY KEY,
	archived_subcategory_id TEXT NOT NULL,
	name TEXT NOT NULL,
	max_score INTEGER NOT NULL,
	FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE
);

CREATE TABLE archived_judge_certifications (
	id TEXT PRIMARY KEY,
	archived_subcategory_id TEXT NOT NULL,
	archived_judge_id TEXT NOT NULL,
	signature_name TEXT NOT NULL,
	certified_at TEXT NOT NULL,
	UNIQUE (archived_subcategory_id, archived_judge_id),
	FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_judge_id) REFERENCES archived_judges(id) ON DELETE CASCADE
);

CREATE TABLE archived_judge_comments (
	id TEXT PRIMARY KEY,
	archived_subcategory_id TEXT NOT NULL,
	archived_contestant_id TEXT NOT NULL,
	archived_judge_id TEXT NOT NULL,
	comment TEXT NOT NULL,
	FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_contestant_id) REFERENCES archived_contestants(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_judge_id) REFERENCES archived_judges(id) ON DELETE CASCADE
);

CREATE TABLE archived_judges (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	email TEXT,
	gender TEXT,
	bio TEXT,
	image_path TEXT
);

CREATE TABLE archived_overall_deductions (
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

CREATE TABLE archived_scores (
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

CREATE TABLE archived_subcategories (
	id TEXT PRIMARY KEY,
	archived_category_id TEXT NOT NULL,
	name TEXT NOT NULL,
	description TEXT,
	score_cap REAL,
	FOREIGN KEY (archived_category_id) REFERENCES archived_categories(id) ON DELETE CASCADE
);

CREATE TABLE archived_subcategory_contestants (
	archived_subcategory_id TEXT NOT NULL,
	archived_contestant_id TEXT NOT NULL,
	PRIMARY KEY (archived_subcategory_id, archived_contestant_id),
	FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_contestant_id) REFERENCES archived_contestants(id) ON DELETE CASCADE
);

CREATE TABLE archived_subcategory_judges (
	archived_subcategory_id TEXT NOT NULL,
	archived_judge_id TEXT NOT NULL,
	PRIMARY KEY (archived_subcategory_id, archived_judge_id),
	FOREIGN KEY (archived_subcategory_id) REFERENCES archived_subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (archived_judge_id) REFERENCES archived_judges(id) ON DELETE CASCADE
);

CREATE TABLE backup_logs (
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

CREATE TABLE "backup_settings" (
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

CREATE TABLE categories (
	id TEXT PRIMARY KEY,
	contest_id TEXT NOT NULL,
	name TEXT NOT NULL,
	FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE CASCADE
);

CREATE TABLE category_contestants (
	category_id TEXT NOT NULL,
	contestant_id TEXT NOT NULL,
	PRIMARY KEY (category_id, contestant_id),
	FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
	FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE
);

CREATE TABLE category_judges (
	category_id TEXT NOT NULL,
	judge_id TEXT NOT NULL,
	PRIMARY KEY (category_id, judge_id),
	FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
	FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE
);

CREATE TABLE contestants (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	email TEXT
, gender TEXT, contestant_number INTEGER, bio TEXT, image_path TEXT, pronouns TEXT);

CREATE TABLE contests (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	start_date TEXT NOT NULL,
	end_date TEXT NOT NULL
);

CREATE TABLE criteria (
	id TEXT PRIMARY KEY,
	subcategory_id TEXT NOT NULL,
	name TEXT NOT NULL,
	max_score INTEGER NOT NULL,
	FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE
);

CREATE TABLE emcee_scripts (
	id TEXT PRIMARY KEY,
	title TEXT NOT NULL,
	description TEXT,
	file_path TEXT NOT NULL,
	file_name TEXT NOT NULL,
	file_size INTEGER,
	file_type TEXT,
	uploaded_by TEXT NOT NULL,
	uploaded_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	is_active BOOLEAN DEFAULT 1, created_at TEXT, filename TEXT, filepath TEXT,
	FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE "judge_certifications" (
  id TEXT PRIMARY KEY,
  subcategory_id TEXT NOT NULL,
  contestant_id TEXT NOT NULL,
  judge_id TEXT NOT NULL,
  signature_name TEXT NOT NULL,
  certified_at TEXT NOT NULL,
  UNIQUE (subcategory_id, contestant_id, judge_id)
);

CREATE TABLE judge_comments (
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

CREATE TABLE judges (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	email TEXT
, gender TEXT, bio TEXT, image_path TEXT, is_head_judge INTEGER NOT NULL DEFAULT 0, pronouns TEXT);

CREATE TABLE overall_deductions (
    id TEXT PRIMARY KEY,
    subcategory_id TEXT NOT NULL,
    contestant_id TEXT NOT NULL,
    amount REAL NOT NULL,
    comment TEXT,
    created_by TEXT,
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP, signature_name TEXT, signed_at TEXT,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
    FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE scores (
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

CREATE TABLE subcategories (
	id TEXT PRIMARY KEY,
	category_id TEXT NOT NULL,
	name TEXT NOT NULL, score_cap INTEGER, description TEXT,
	FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE subcategory_contestants (
	subcategory_id TEXT NOT NULL,
	contestant_id TEXT NOT NULL,
	PRIMARY KEY (subcategory_id, contestant_id),
	FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (contestant_id) REFERENCES contestants(id) ON DELETE CASCADE
);

CREATE TABLE subcategory_judges (
	subcategory_id TEXT NOT NULL,
	judge_id TEXT NOT NULL,
	PRIMARY KEY (subcategory_id, judge_id),
	FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE CASCADE,
	FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE CASCADE
);

CREATE TABLE subcategory_templates (
	id TEXT PRIMARY KEY,
	name TEXT NOT NULL,
	description TEXT
, subcategory_names TEXT, max_score INTEGER DEFAULT 60);

CREATE TABLE system_settings (
	id TEXT PRIMARY KEY,
	setting_key TEXT UNIQUE NOT NULL,
	setting_value TEXT NOT NULL,
	description TEXT,
	updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_by TEXT,
	FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE template_criteria (
	id TEXT PRIMARY KEY,
	template_id TEXT NOT NULL,
	name TEXT NOT NULL,
	max_score INTEGER NOT NULL,
	FOREIGN KEY (template_id) REFERENCES subcategory_templates(id) ON DELETE CASCADE
);

CREATE TABLE "users" (
					id TEXT PRIMARY KEY,
					name TEXT NOT NULL,
					preferred_name TEXT,
					email TEXT UNIQUE,
					password_hash TEXT,
					role TEXT NOT NULL CHECK (role IN ('organizer','judge','emcee','contestant')),
					judge_id TEXT,
					gender TEXT, session_version INTEGER NOT NULL DEFAULT 1, last_login TEXT, contestant_id TEXT, pronouns TEXT,
					FOREIGN KEY (judge_id) REFERENCES judges(id) ON DELETE SET NULL
				);

CREATE INDEX idx_activity_logs_action ON activity_logs(action);

CREATE INDEX idx_activity_logs_created_at ON activity_logs(created_at);

CREATE INDEX idx_activity_logs_user_id ON activity_logs(user_id);

CREATE INDEX idx_categories_contest_id ON categories(contest_id);

CREATE INDEX idx_contestants_contestant_number ON contestants(contestant_number);

CREATE INDEX idx_contestants_name ON contestants(name);

CREATE INDEX idx_criteria_subcategory_id ON criteria(subcategory_id);

CREATE INDEX idx_emcee_scripts_created_at ON emcee_scripts(created_at);

CREATE INDEX idx_emcee_scripts_is_active ON emcee_scripts(is_active);

CREATE INDEX idx_emcee_scripts_uploaded_by ON emcee_scripts(uploaded_by);

CREATE INDEX idx_judge_certifications_certified_at ON judge_certifications(certified_at);

CREATE INDEX idx_judge_certifications_contestant_id ON judge_certifications(contestant_id);

CREATE INDEX idx_judge_certifications_judge_id ON judge_certifications(judge_id);

CREATE INDEX idx_judges_is_head_judge ON judges(is_head_judge);

CREATE INDEX idx_judges_name ON judges(name);

CREATE INDEX idx_scores_contestant_id ON scores(contestant_id);

CREATE INDEX idx_scores_created_at ON scores(created_at);

CREATE INDEX idx_scores_criterion_id ON scores(criterion_id);

CREATE INDEX idx_scores_judge_id ON scores(judge_id);

CREATE INDEX idx_scores_subcategory_id ON scores(subcategory_id);

CREATE INDEX idx_subcategories_category_id ON subcategories(category_id);

CREATE INDEX idx_subcategory_contestants_contestant_id ON subcategory_contestants(contestant_id);

CREATE INDEX idx_subcategory_contestants_subcategory_id ON subcategory_contestants(subcategory_id);

CREATE INDEX idx_subcategory_judges_judge_id ON subcategory_judges(judge_id);

CREATE INDEX idx_subcategory_judges_subcategory_id ON subcategory_judges(subcategory_id);

CREATE INDEX idx_users_email ON users(email);

CREATE INDEX idx_users_last_login ON users(last_login);

CREATE INDEX idx_users_preferred_name ON users(preferred_name);

CREATE INDEX idx_users_role ON users(role);

CREATE UNIQUE INDEX judge_certifications_unique
  ON judge_certifications (subcategory_id, contestant_id, judge_id);

