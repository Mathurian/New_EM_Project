<?php
declare(strict_types=1);

namespace App;

/**
 * Enhanced pagination service with caching and performance optimizations
 */
class PaginationService
{
    /**
     * Get paginated results with optimized queries
     */
    public static function paginate(string $table, array $options = []): array
    {
        $page = (int)($options['page'] ?? 1);
        $perPage = (int)($options['per_page'] ?? 20);
        $where = $options['where'] ?? '';
        $params = $options['params'] ?? [];
        $orderBy = $options['order_by'] ?? 'id';
        $joins = $options['joins'] ?? '';
        $select = $options['select'] ?? '*';
        
        // Validate inputs
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage)); // Cap at 100 items per page
        
        $offset = ($page - 1) * $perPage;
        
        // Build query
        $whereClause = $where ? "WHERE {$where}" : '';
        $sql = "SELECT {$select} FROM {$table} {$joins} {$whereClause} ORDER BY {$orderBy} LIMIT ? OFFSET ?";
        
        $queryParams = array_merge($params, [$perPage, $offset]);
        
        // Get results
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute($queryParams);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Get total count
        $countSql = "SELECT COUNT(*) as total FROM {$table} {$joins} {$whereClause}";
        $countStmt = DB::pdo()->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        
        // Calculate pagination info
        $totalPages = ceil($total / $perPage);
        $hasNext = $page < $totalPages;
        $hasPrev = $page > 1;
        
        return [
            'items' => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next' => $hasNext,
                'has_prev' => $hasPrev,
                'next_page' => $hasNext ? $page + 1 : null,
                'prev_page' => $hasPrev ? $page - 1 : null,
                'offset' => $offset
            ]
        ];
    }

    /**
     * Get paginated users with role filtering
     */
    public static function getUsers(int $page = 1, int $perPage = 20, string $role = null): array
    {
        $options = [
            'page' => $page,
            'per_page' => $perPage,
            'select' => 'u.*, c.name as contestant_name, c.contestant_number, j.is_head_judge',
            'joins' => 'LEFT JOIN contestants c ON u.contestant_id = c.id LEFT JOIN judges j ON u.judge_id = j.id',
            'order_by' => 'u.created_at DESC'
        ];
        
        if ($role) {
            $options['where'] = 'u.role = ?';
            $options['params'] = [$role];
        }
        
        return self::paginate('users u', $options);
    }

    /**
     * Get paginated contests
     */
    public static function getContests(int $page = 1, int $perPage = 20): array
    {
        return self::paginate('contests', [
            'page' => $page,
            'per_page' => $perPage,
            'order_by' => 'start_date DESC'
        ]);
    }

    /**
     * Get paginated activity logs
     */
    public static function getActivityLogs(int $page = 1, int $perPage = 50, string $level = null): array
    {
        $options = [
            'page' => $page,
            'per_page' => $perPage,
            'order_by' => 'created_at DESC'
        ];
        
        if ($level) {
            $options['where'] = 'level = ?';
            $options['params'] = [$level];
        }
        
        return self::paginate('activity_logs', $options);
    }

    /**
     * Generate pagination links HTML
     */
    public static function generateLinks(array $pagination, string $baseUrl, array $queryParams = []): string
    {
        if ($pagination['total_pages'] <= 1) {
            return '';
        }
        
        $currentPage = $pagination['current_page'];
        $totalPages = $pagination['total_pages'];
        
        // Build query string
        $queryString = http_build_query($queryParams);
        $separator = $queryString ? '&' : '?';
        
        $html = '<nav class="pagination" aria-label="Pagination">';
        $html .= '<ul class="pagination-list">';
        
        // Previous button
        if ($pagination['has_prev']) {
            $prevUrl = $baseUrl . $separator . 'page=' . $pagination['prev_page'];
            if ($queryString) $prevUrl .= '&' . $queryString;
            $html .= '<li><a href="' . htmlspecialchars($prevUrl) . '" class="pagination-link" aria-label="Previous page">← Previous</a></li>';
        }
        
        // Page numbers
        $start = max(1, $currentPage - 2);
        $end = min($totalPages, $currentPage + 2);
        
        // First page
        if ($start > 1) {
            $url = $baseUrl . $separator . 'page=1';
            if ($queryString) $url .= '&' . $queryString;
            $html .= '<li><a href="' . htmlspecialchars($url) . '" class="pagination-link">1</a></li>';
            if ($start > 2) {
                $html .= '<li><span class="pagination-ellipsis">…</span></li>';
            }
        }
        
        // Page range
        for ($i = $start; $i <= $end; $i++) {
            $url = $baseUrl . $separator . 'page=' . $i;
            if ($queryString) $url .= '&' . $queryString;
            
            $class = $i === $currentPage ? 'pagination-link is-current' : 'pagination-link';
            $html .= '<li><a href="' . htmlspecialchars($url) . '" class="' . $class . '" aria-label="Page ' . $i . '">' . $i . '</a></li>';
        }
        
        // Last page
        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                $html .= '<li><span class="pagination-ellipsis">…</span></li>';
            }
            $url = $baseUrl . $separator . 'page=' . $totalPages;
            if ($queryString) $url .= '&' . $queryString;
            $html .= '<li><a href="' . htmlspecialchars($url) . '" class="pagination-link">' . $totalPages . '</a></li>';
        }
        
        // Next button
        if ($pagination['has_next']) {
            $nextUrl = $baseUrl . $separator . 'page=' . $pagination['next_page'];
            if ($queryString) $nextUrl .= '&' . $queryString;
            $html .= '<li><a href="' . htmlspecialchars($nextUrl) . '" class="pagination-link" aria-label="Next page">Next →</a></li>';
        }
        
        $html .= '</ul>';
        $html .= '</nav>';
        
        return $html;
    }

    /**
     * Get pagination info for display
     */
    public static function getInfo(array $pagination): string
    {
        $start = $pagination['offset'] + 1;
        $end = min($pagination['offset'] + $pagination['per_page'], $pagination['total']);
        
        return "Showing {$start}-{$end} of {$pagination['total']} results";
    }
}