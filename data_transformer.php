#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Data Transformation Script for Schema Cleanup
 * 
 * This script handles data transformation during schema cleanup migration
 */

require_once __DIR__ . '/app/bootstrap.php';

use App\DB;
use App\DatabaseFactory;
use App\Logger;

class DataTransformer {
    private $sourceDb;
    private $targetDb;
    private $cleanupEnabled;
    private $errors = [];
    private $logMessages = [];

    public function __construct($sourceDb, $targetDb, $cleanupEnabled = false) {
        $this->sourceDb = $sourceDb;
        $this->targetDb = $targetDb;
        $this->cleanupEnabled = $cleanupEnabled;
    }

    public function transformData(): bool {
        $this->log("Starting data transformation...");
        
        try {
            if ($this->cleanupEnabled) {
                $this->log("🧹 Data cleanup enabled - transforming to new structure");
                $this->transformWithCleanup();
            } else {
                $this->log("📋 Standard data migration - maintaining current structure");
                $this->transformStandard();
            }
            
            if (!empty($this->errors)) {
                $this->log("Data transformation completed with errors.", 'warning');
                return false;
            }
            
            $this->log("Data transformation completed successfully.");
            return true;
        } catch (Exception $e) {
            $this->log("Data transformation failed: " . $e->getMessage(), 'error');
            $this->errors[] = $e->getMessage();
            return false;
        }
    }

    private function transformWithCleanup(): void {
        $this->log("Transforming data with schema cleanup...");
        
        // 1. Transform contests → events
        $this->transformContestsToEvents();
        
        // 2. Transform categories → contest_groups
        $this->transformCategoriesToContestGroups();
        
        // 3. Transform subcategories → categories
        $this->transformSubcategoriesToCategories();
        
        // 4. Transform users (consolidate judges and contestants)
        $this->transformUsersWithConsolidation();
        
        // 5. Transform other tables with updated foreign keys
        $this->transformOtherTables();
        
        // 6. Transform archived tables
        $this->transformArchivedTables();
    }

    private function transformStandard(): void {
        $this->log("Performing standard data migration...");
        
        // Use existing DataMigrator logic
        $migrator = new \App\DataMigrator($this->sourceDb, $this->targetDb);
        $migrator->migrateData();
    }

    private function transformContestsToEvents(): void {
        $this->log("Transforming contests to events...");
        
        $contests = $this->sourceDb->query("SELECT * FROM contests");
        
        foreach ($contests as $contest) {
            $sql = "
                INSERT INTO events (id, name, start_date, end_date, description, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            
            $this->targetDb->execute($sql, [
                $contest['id'],
                $contest['name'],
                $contest['start_date'],
                $contest['end_date'],
                $contest['description'] ?? null,
                $contest['created_at'] ?? date('Y-m-d H:i:s'),
                $contest['updated_at'] ?? date('Y-m-d H:i:s')
            ]);
        }
        
        $this->log("Transformed " . count($contests) . " contests to events");
    }

    private function transformCategoriesToContestGroups(): void {
        $this->log("Transforming categories to contest_groups...");
        
        $categories = $this->sourceDb->query("SELECT * FROM categories");
        
        foreach ($categories as $category) {
            $sql = "
                INSERT INTO contest_groups (id, event_id, name, description, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            
            $this->targetDb->execute($sql, [
                $category['id'],
                $category['contest_id'], // contest_id becomes event_id
                $category['name'],
                $category['description'] ?? null,
                $category['created_at'] ?? date('Y-m-d H:i:s'),
                $category['updated_at'] ?? date('Y-m-d H:i:s')
            ]);
        }
        
        $this->log("Transformed " . count($categories) . " categories to contest_groups");
    }

    private function transformSubcategoriesToCategories(): void {
        $this->log("Transforming subcategories to categories...");
        
        $subcategories = $this->sourceDb->query("SELECT * FROM subcategories");
        
        foreach ($subcategories as $subcategory) {
            $sql = "
                INSERT INTO categories (id, contest_group_id, name, description, score_cap, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            
            $this->targetDb->execute($sql, [
                $subcategory['id'],
                $subcategory['category_id'], // category_id becomes contest_group_id
                $subcategory['name'],
                $subcategory['description'] ?? null,
                $subcategory['score_cap'] ?? null,
                $subcategory['created_at'] ?? date('Y-m-d H:i:s'),
                $subcategory['updated_at'] ?? date('Y-m-d H:i:s')
            ]);
        }
        
        $this->log("Transformed " . count($subcategories) . " subcategories to categories");
    }

    private function transformUsersWithConsolidation(): void {
        $this->log("Transforming users with consolidation...");
        
        // Get all users
        $users = $this->sourceDb->query("SELECT * FROM users");
        $judges = $this->sourceDb->query("SELECT * FROM judges");
        $contestants = $this->sourceDb->query("SELECT * FROM contestants");
        
        // Create lookup arrays
        $judgeLookup = [];
        foreach ($judges as $judge) {
            $judgeLookup[$judge['id']] = $judge;
        }
        
        $contestantLookup = [];
        foreach ($contestants as $contestant) {
            $contestantLookup[$contestant['id']] = $contestant;
        }
        
        // Transform users
        foreach ($users as $user) {
            $role = $user['role'];
            
            // Set role flags
            $isOrganizer = ($role === 'organizer');
            $isJudge = ($role === 'judge');
            $isContestant = ($role === 'contestant');
            $isEmcee = ($role === 'emcee');
            $isTallyMaster = ($role === 'tally_master');
            $isAuditor = ($role === 'auditor');
            $isBoard = ($role === 'board');
            
            // Get judge-specific data
            $isHeadJudge = false;
            $judgeBio = null;
            $judgeImagePath = null;
            
            if ($isJudge && isset($judgeLookup[$user['judge_id']])) {
                $judgeData = $judgeLookup[$user['judge_id']];
                $isHeadJudge = (bool) $judgeData['is_head_judge'];
                $judgeBio = $judgeData['bio'];
                $judgeImagePath = $judgeData['image_path'];
            }
            
            // Get contestant-specific data
            $contestantNumber = null;
            $contestantBio = null;
            $contestantImagePath = null;
            
            if ($isContestant && isset($contestantLookup[$user['contestant_id']])) {
                $contestantData = $contestantLookup[$user['contestant_id']];
                $contestantNumber = $contestantData['contestant_number'];
                $contestantBio = $contestantData['bio'];
                $contestantImagePath = $contestantData['image_path'];
            }
            
            $sql = "
                INSERT INTO users (
                    id, name, preferred_name, email, password_hash, role,
                    is_organizer, is_judge, is_contestant, is_emcee, is_tally_master, is_auditor, is_board,
                    is_head_judge, judge_bio, judge_image_path,
                    contestant_number, contestant_bio, contestant_image_path,
                    gender, pronouns, session_version, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $this->targetDb->execute($sql, [
                $user['id'],
                $user['name'],
                $user['preferred_name'],
                $user['email'],
                $user['password_hash'],
                $role,
                $isOrganizer, $isJudge, $isContestant, $isEmcee, $isTallyMaster, $isAuditor, $isBoard,
                $isHeadJudge, $judgeBio, $judgeImagePath,
                $contestantNumber, $contestantBio, $contestantImagePath,
                $user['gender'],
                $user['pronouns'],
                $user['session_version'],
                $user['created_at'],
                $user['updated_at'] ?? $user['created_at']
            ]);
        }
        
        $this->log("Transformed " . count($users) . " users with consolidation");
    }

    private function transformOtherTables(): void {
        $this->log("Transforming other tables...");
        
        // Transform criteria (subcategory_id → category_id)
        $criteria = $this->sourceDb->query("SELECT * FROM criteria");
        foreach ($criteria as $criterion) {
            $sql = "
                INSERT INTO criteria (id, category_id, name, max_score, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            $this->targetDb->execute($sql, [
                $criterion['id'],
                $criterion['subcategory_id'], // subcategory_id becomes category_id
                $criterion['name'],
                $criterion['max_score'],
                $criterion['created_at'] ?? date('Y-m-d H:i:s'),
                $criterion['updated_at'] ?? date('Y-m-d H:i:s')
            ]);
        }
        
        // Transform scores (subcategory_id → category_id, contestant_id/judge_id → user_id)
        $scores = $this->sourceDb->query("SELECT * FROM scores");
        foreach ($scores as $score) {
            $sql = "
                INSERT INTO scores (id, category_id, contestant_id, judge_id, criterion_id, score, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            $this->targetDb->execute($sql, [
                $score['id'],
                $score['subcategory_id'], // subcategory_id becomes category_id
                $score['contestant_id'], // These are now user IDs
                $score['judge_id'], // These are now user IDs
                $score['criterion_id'],
                $score['score'],
                $score['created_at']
            ]);
        }
        
        // Transform judge comments
        $comments = $this->sourceDb->query("SELECT * FROM judge_comments");
        foreach ($comments as $comment) {
            $sql = "
                INSERT INTO judge_comments (id, category_id, contestant_id, judge_id, comment, created_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            $this->targetDb->execute($sql, [
                $comment['id'],
                $comment['subcategory_id'], // subcategory_id becomes category_id
                $comment['contestant_id'], // These are now user IDs
                $comment['judge_id'], // These are now user IDs
                $comment['comment'],
                $comment['created_at']
            ]);
        }
        
        // Transform certifications
        $this->transformCertifications();
        
        // Transform system tables
        $this->transformSystemTables();
        
        $this->log("Transformed other tables successfully");
    }

    private function transformCertifications(): void {
        // Tally master certifications
        $tallyCerts = $this->sourceDb->query("SELECT * FROM tally_master_certifications");
        foreach ($tallyCerts as $cert) {
            $sql = "
                INSERT INTO tally_master_certifications (id, category_id, signature_name, certified_at)
                VALUES (?, ?, ?, ?)
            ";
            $this->targetDb->execute($sql, [
                $cert['id'],
                $cert['subcategory_id'], // subcategory_id becomes category_id
                $cert['signature_name'],
                $cert['certified_at']
            ]);
        }
        
        // Auditor certifications
        $auditorCerts = $this->sourceDb->query("SELECT * FROM auditor_certifications");
        foreach ($auditorCerts as $cert) {
            $sql = "
                INSERT INTO auditor_certifications (id, category_id, signature_name, certified_at)
                VALUES (?, ?, ?, ?)
            ";
            $this->targetDb->execute($sql, [
                $cert['id'],
                $cert['subcategory_id'], // subcategory_id becomes category_id
                $cert['signature_name'],
                $cert['certified_at']
            ]);
        }
        
        // Judge certifications
        $judgeCerts = $this->sourceDb->query("SELECT * FROM judge_certifications");
        foreach ($judgeCerts as $cert) {
            $sql = "
                INSERT INTO judge_certifications (id, category_id, judge_id, signature_name, certified_at)
                VALUES (?, ?, ?, ?, ?)
            ";
            $this->targetDb->execute($sql, [
                $cert['id'],
                $cert['subcategory_id'], // subcategory_id becomes category_id
                $cert['judge_id'], // This is now a user ID
                $cert['signature_name'],
                $cert['certified_at']
            ]);
        }
    }

    private function transformSystemTables(): void {
        // Activity logs
        $logs = $this->sourceDb->query("SELECT * FROM activity_logs");
        foreach ($logs as $log) {
            $sql = "
                INSERT INTO activity_logs (id, user_id, user_name, user_role, action, resource_type, resource_id, details, ip_address, user_agent, log_level, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $this->targetDb->execute($sql, [
                $log['id'],
                $log['user_id'],
                $log['user_name'],
                $log['user_role'],
                $log['action'],
                $log['resource_type'],
                $log['resource_id'],
                $log['details'],
                $log['ip_address'],
                $log['user_agent'],
                $log['log_level'],
                $log['created_at']
            ]);
        }
        
        // System settings
        $settings = $this->sourceDb->query("SELECT * FROM system_settings");
        foreach ($settings as $setting) {
            $sql = "
                INSERT INTO system_settings (id, setting_key, setting_value, description, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?)
            ";
            $this->targetDb->execute($sql, [
                $setting['id'],
                $setting['setting_key'],
                $setting['setting_value'],
                $setting['description'],
                $setting['created_at'],
                $setting['updated_at']
            ]);
        }
        
        // Backup settings
        $backupSettings = $this->sourceDb->query("SELECT * FROM backup_settings");
        foreach ($backupSettings as $setting) {
            $sql = "
                INSERT INTO backup_settings (id, backup_type, enabled, frequency, frequency_value, retention_days, last_run, next_run, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $this->targetDb->execute($sql, [
                $setting['id'],
                $setting['backup_type'],
                $setting['enabled'],
                $setting['frequency'],
                $setting['frequency_value'],
                $setting['retention_days'],
                $setting['last_run'],
                $setting['next_run'],
                $setting['created_at'],
                $setting['updated_at']
            ]);
        }
        
        // Backup logs
        $backupLogs = $this->sourceDb->query("SELECT * FROM backup_logs");
        foreach ($backupLogs as $log) {
            $sql = "
                INSERT INTO backup_logs (id, backup_type, file_path, file_size, status, created_by, created_at, error_message)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $this->targetDb->execute($sql, [
                $log['id'],
                $log['backup_type'],
                $log['file_path'],
                $log['file_size'],
                $log['status'],
                $log['created_by'],
                $log['created_at'],
                $log['error_message']
            ]);
        }
    }

    private function transformArchivedTables(): void {
        $this->log("Transforming archived tables...");
        
        // Transform archived contests → archived events
        $archivedContests = $this->sourceDb->query("SELECT * FROM archived_contests");
        foreach ($archivedContests as $contest) {
            $sql = "
                INSERT INTO archived_events (id, name, description, start_date, end_date, archived_at, archived_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";
            $this->targetDb->execute($sql, [
                $contest['id'],
                $contest['name'],
                $contest['description'],
                $contest['start_date'],
                $contest['end_date'],
                $contest['archived_at'],
                $contest['archived_by']
            ]);
        }
        
        // Transform archived categories → archived contest groups
        $archivedCategories = $this->sourceDb->query("SELECT * FROM archived_categories");
        foreach ($archivedCategories as $category) {
            $sql = "
                INSERT INTO archived_contest_groups (id, archived_event_id, name, description)
                VALUES (?, ?, ?, ?)
            ";
            $this->targetDb->execute($sql, [
                $category['id'],
                $category['archived_contest_id'], // archived_contest_id becomes archived_event_id
                $category['name'],
                $category['description']
            ]);
        }
        
        // Transform archived subcategories → archived categories
        $archivedSubcategories = $this->sourceDb->query("SELECT * FROM archived_subcategories");
        foreach ($archivedSubcategories as $subcategory) {
            $sql = "
                INSERT INTO archived_categories (id, archived_contest_group_id, name, description, score_cap)
                VALUES (?, ?, ?, ?, ?)
            ";
            $this->targetDb->execute($sql, [
                $subcategory['id'],
                $subcategory['archived_category_id'], // archived_category_id becomes archived_contest_group_id
                $subcategory['name'],
                $subcategory['description'],
                $subcategory['score_cap']
            ]);
        }
        
        $this->log("Transformed archived tables successfully");
    }

    private function log(string $message, string $level = 'info'): void {
        $this->logMessages[] = ['level' => $level, 'message' => $message];
        Logger::log($message, $level);
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function getLogMessages(): array {
        return $this->logMessages;
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    echo "🔄 Data Transformer for Schema Cleanup\n";
    echo "=====================================\n\n";
    
    $cleanupEnabled = in_array('--cleanup', $argv);
    
    if ($cleanupEnabled) {
        echo "✅ Data cleanup enabled\n";
        echo "   - Consolidating users, judges, contestants\n";
        echo "   - Updating foreign key references\n";
        echo "   - Transforming table relationships\n\n";
    } else {
        echo "📋 Standard data migration (no cleanup)\n";
        echo "   Use --cleanup flag to enable data transformation\n\n";
    }
    
    echo "Usage: php data_transformer.php [--cleanup]\n";
}
