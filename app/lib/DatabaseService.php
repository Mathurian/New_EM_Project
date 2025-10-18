<?php
declare(strict_types=1);

namespace App;

/**
 * Optimized database service with query caching and N+1 prevention
 */
class DatabaseService
{
    private static array $queryCache = [];
    private static int $cacheHits = 0;
    private static int $cacheMisses = 0;

    /**
     * Get contests with pagination and caching
     */
    public static function getContests(int $page = 1, int $perPage = 20): array
    {
        $cacheKey = "contests_page_{$page}_per_{$perPage}";
        
        return Cache::remember($cacheKey, function() use ($page, $perPage) {
            $offset = ($page - 1) * $perPage;
            $stmt = DB::pdo()->prepare('
                SELECT * FROM contests 
                ORDER BY start_date DESC 
                LIMIT ? OFFSET ?
            ');
            $stmt->execute([$perPage, $offset]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }, 300); // 5 minutes cache
    }

    /**
     * Get contest with all related data in optimized queries
     */
    public static function getContestWithDetails(string $contestId): ?array
    {
        $cacheKey = "contest_details_{$contestId}";
        
        return Cache::remember($cacheKey, function() use ($contestId) {
            $pdo = DB::pdo();
            
            // Get contest
            $stmt = $pdo->prepare('SELECT * FROM contests WHERE id = ?');
            $stmt->execute([$contestId]);
            $contest = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$contest) {
                return null;
            }

            // Get categories with subcategories and criteria in optimized queries
            $stmt = $pdo->prepare('
                SELECT c.*, 
                       COUNT(DISTINCT s.id) as subcategory_count,
                       COUNT(DISTINCT cr.id) as criteria_count
                FROM categories c
                LEFT JOIN subcategories s ON c.id = s.category_id
                LEFT JOIN criteria cr ON s.id = cr.subcategory_id
                WHERE c.contest_id = ?
                GROUP BY c.id
                ORDER BY c.name
            ');
            $stmt->execute([$contestId]);
            $categories = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Get all subcategories for this contest
            $stmt = $pdo->prepare('
                SELECT s.*, c.name as category_name
                FROM subcategories s
                JOIN categories c ON s.category_id = c.id
                WHERE c.contest_id = ?
                ORDER BY c.name, s.name
            ');
            $stmt->execute([$contestId]);
            $subcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Get all criteria for this contest
            $stmt = $pdo->prepare('
                SELECT cr.*, s.name as subcategory_name, c.name as category_name
                FROM criteria cr
                JOIN subcategories s ON cr.subcategory_id = s.id
                JOIN categories c ON s.category_id = c.id
                WHERE c.contest_id = ?
                ORDER BY c.name, s.name, cr.name
            ');
            $stmt->execute([$contestId]);
            $criteria = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Organize data hierarchically
            $contest['categories'] = [];
            foreach ($categories as $category) {
                $category['subcategories'] = array_filter($subcategories, function($sub) use ($category) {
                    return $sub['category_id'] === $category['id'];
                });
                
                // Add criteria to subcategories
                foreach ($category['subcategories'] as &$subcategory) {
                    $subcategory['criteria'] = array_filter($criteria, function($criterion) use ($subcategory) {
                        return $criterion['subcategory_id'] === $subcategory['id'];
                    });
                }
                
                $contest['categories'][] = $category;
            }

            return $contest;
        }, 600); // 10 minutes cache
    }

    /**
     * Get users with pagination and role-specific data
     */
    public static function getUsers(int $page = 1, int $perPage = 20, string $role = null): array
    {
        $cacheKey = "users_page_{$page}_per_{$perPage}_role_{$role}";
        
        return Cache::remember($cacheKey, function() use ($page, $perPage, $role) {
            $offset = ($page - 1) * $perPage;
            $whereClause = $role ? 'WHERE u.role = ?' : '';
            $params = $role ? [$role, $perPage, $offset] : [$perPage, $offset];
            
            $stmt = DB::pdo()->prepare("
                SELECT u.*, 
                       c.name as contestant_name,
                       c.contestant_number,
                       j.is_head_judge,
                       e.bio as emcee_bio
                FROM users u
                LEFT JOIN contestants c ON u.contestant_id = c.id
                LEFT JOIN judges j ON u.judge_id = j.id
                LEFT JOIN emcees e ON u.emcee_id = e.id
                {$whereClause}
                ORDER BY u.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }, 300); // 5 minutes cache
    }

    /**
     * Get contestant scores with all related data in optimized query
     */
    public static function getContestantScores(string $contestantId, string $subcategoryId = null): array
    {
        $cacheKey = "contestant_scores_{$contestantId}_{$subcategoryId}";
        
        return Cache::remember($cacheKey, function() use ($contestantId, $subcategoryId) {
            $whereClause = $subcategoryId ? 'AND s.subcategory_id = ?' : '';
            $params = $subcategoryId ? [$contestantId, $subcategoryId] : [$contestantId];
            
            $stmt = DB::pdo()->prepare("
                SELECT s.*, 
                       c.name as criterion_name,
                       c.max_score,
                       c.weight,
                       sub.name as subcategory_name,
                       cat.name as category_name,
                       cont.name as contestant_name,
                       u.name as judge_name
                FROM scores s
                JOIN criteria c ON s.criterion_id = c.id
                JOIN subcategories sub ON c.subcategory_id = sub.id
                JOIN categories cat ON sub.category_id = cat.id
                JOIN contestants cont ON s.contestant_id = cont.id
                JOIN users u ON s.judge_id = u.id
                WHERE s.contestant_id = ? {$whereClause}
                ORDER BY cat.name, sub.name, c.name
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }, 180); // 3 minutes cache
    }

    /**
     * Get results summary with optimized aggregation
     */
    public static function getResultsSummary(string $contestId): array
    {
        $cacheKey = "results_summary_{$contestId}";
        
        return Cache::remember($cacheKey, function() use ($contestId) {
            $stmt = DB::pdo()->prepare("
                SELECT 
                    c.id as contestant_id,
                    c.name as contestant_name,
                    c.contestant_number,
                    cat.name as category_name,
                    sub.name as subcategory_name,
                    AVG(s.score) as average_score,
                    COUNT(s.id) as score_count,
                    SUM(cr.weight) as total_weight
                FROM contestants c
                JOIN subcategory_contestants sc ON c.id = sc.contestant_id
                JOIN subcategories sub ON sc.subcategory_id = sub.id
                JOIN categories cat ON sub.category_id = cat.id
                LEFT JOIN scores s ON c.id = s.contestant_id AND sub.id = s.subcategory_id
                LEFT JOIN criteria cr ON s.criterion_id = cr.id
                WHERE cat.contest_id = ?
                GROUP BY c.id, cat.id, sub.id
                ORDER BY cat.name, sub.name, average_score DESC
            ");
            $stmt->execute([$contestId]);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }, 300); // 5 minutes cache
    }

    /**
     * Clear cache for specific contest
     */
    public static function clearContestCache(string $contestId): void
    {
        $patterns = [
            "contest_details_{$contestId}",
            "results_summary_{$contestId}",
            "contests_page_*",
            "users_page_*"
        ];
        
        foreach ($patterns as $pattern) {
            if (strpos($pattern, '*') !== false) {
                // Clear all matching keys
                $files = glob(Cache::$cacheDir . '*.cache');
                foreach ($files as $file) {
                    $key = basename($file, '.cache');
                    if (fnmatch($pattern, $key)) {
                        unlink($file);
                    }
                }
            } else {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * Get cache statistics
     */
    public static function getCacheStats(): array
    {
        return [
            'cache_stats' => Cache::getStats(),
            'query_cache_hits' => self::$cacheHits,
            'query_cache_misses' => self::$cacheMisses,
            'hit_rate' => self::$cacheHits + self::$cacheMisses > 0 
                ? round((self::$cacheHits / (self::$cacheHits + self::$cacheMisses)) * 100, 2) 
                : 0
        ];
    }

    /**
     * Optimized query with built-in caching
     */
    public static function query(string $sql, array $params = [], int $cacheTtl = 300)
    {
        $cacheKey = 'query_' . md5($sql . serialize($params));
        
        return Cache::remember($cacheKey, function() use ($sql, $params) {
            $stmt = DB::pdo()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }, $cacheTtl);
    }
}