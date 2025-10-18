<?php
declare(strict_types=1);

namespace App\Controllers;

use App\{DB, Logger, DatabaseService, Cache, SecurityService};

/**
 * Admin panel controller
 */
class AdminController extends BaseController
{
    public function index(): void
    {
        $this->requireOrganizer();
        
        // Get system statistics
        $stats = $this->getSystemStats();
        
        $this->renderView('admin/index', compact('stats'));
    }

    public function users(): void
    {
        $this->requireOrganizer();
        
        $pagination = $this->getPaginationParams();
        $searchParams = $this->getSearchParams();
        
        $users = PaginationService::getUsers(
            $pagination['page'], 
            $pagination['per_page'], 
            $searchParams['role'] ?: null
        );
        
        $this->renderView('admin/users', [
            'users' => $users['items'],
            'pagination' => $users['pagination'],
            'search' => $searchParams
        ]);
    }

    public function settings(): void
    {
        $this->requireOrganizer();
        
        $settings = $this->getSystemSettings();
        
        $this->renderView('admin/settings', compact('settings'));
    }

    public function updateSettings(): void
    {
        $this->requireOrganizer();
        $this->validateCsrf();
        
        $inputData = $this->sanitizeInput($_POST);
        
        try {
            foreach ($inputData as $key => $value) {
                $this->updateSetting($key, $value);
            }
            
            $this->logAction('settings_updated', 'system', null, 'System settings updated');
            
            $this->redirectWithMessage('/admin/settings', 'Settings updated successfully');
            
        } catch (\Exception $e) {
            $this->handleError('Failed to update settings: ' . $e->getMessage());
        }
    }

    public function logs(): void
    {
        $this->requireOrganizer();
        
        $pagination = $this->getPaginationParams();
        $searchParams = $this->getSearchParams();
        
        $logs = PaginationService::getActivityLogs(
            $pagination['page'], 
            $pagination['per_page'], 
            $searchParams['level'] ?: null
        );
        
        $this->renderView('admin/logs', [
            'logs' => $logs['items'],
            'pagination' => $logs['pagination'],
            'search' => $searchParams
        ]);
    }

    public function cacheStats(): void
    {
        $this->requireOrganizer();
        
        $stats = DatabaseService::getCacheStats();
        
        $this->renderJson($stats);
    }

    public function clearCache(): void
    {
        $this->requireOrganizer();
        $this->validateCsrf();
        
        try {
            Cache::flush();
            $this->logAction('cache_cleared', 'system', null, 'Application cache cleared');
            $this->redirectWithMessage('/admin', 'Cache cleared successfully');
        } catch (\Exception $e) {
            $this->handleError('Failed to clear cache: ' . $e->getMessage());
        }
    }

    public function databaseStats(): void
    {
        $this->requireOrganizer();
        
        $stats = $this->getDatabaseStats();
        
        $this->renderJson($stats);
    }

    private function getSystemStats(): array
    {
        return Cache::remember('system_stats', function() {
            $stats = [];
            
            // User counts by role
            $stmt = DB::pdo()->query('SELECT role, COUNT(*) as count FROM users GROUP BY role');
            $stats['users_by_role'] = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
            
            // Contest counts
            $stmt = DB::pdo()->query('SELECT COUNT(*) FROM contests');
            $stats['total_contests'] = $stmt->fetchColumn();
            
            $stmt = DB::pdo()->query('SELECT COUNT(*) FROM contests WHERE archived = 1');
            $stats['archived_contests'] = $stmt->fetchColumn();
            
            // Recent activity
            $stmt = DB::pdo()->query('SELECT COUNT(*) FROM activity_logs WHERE created_at > datetime("now", "-24 hours")');
            $stats['recent_activity'] = $stmt->fetchColumn();
            
            // Cache stats
            $stats['cache'] = DatabaseService::getCacheStats();
            
            return $stats;
        }, 300); // 5 minutes cache
    }

    private function getSystemSettings(): array
    {
        $stmt = DB::pdo()->query('SELECT setting_key, setting_value FROM system_settings');
        $settings = [];
        
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    }

    private function updateSetting(string $key, string $value): void
    {
        $stmt = DB::pdo()->prepare('
            INSERT OR REPLACE INTO system_settings (setting_key, setting_value, updated_at) 
            VALUES (?, ?, ?)
        ');
        $stmt->execute([$key, $value, date('c')]);
    }

    private function getDatabaseStats(): array
    {
        $stats = [];
        
        // Table sizes
        $tables = ['users', 'contests', 'categories', 'subcategories', 'contestants', 'scores', 'activity_logs'];
        
        foreach ($tables as $table) {
            $stmt = DB::pdo()->query("SELECT COUNT(*) FROM {$table}");
            $stats[$table] = $stmt->fetchColumn();
        }
        
        // Database file size
        $dbPath = DB::getDatabasePath();
        $stats['file_size'] = file_exists($dbPath) ? filesize($dbPath) : 0;
        
        return $stats;
    }
}