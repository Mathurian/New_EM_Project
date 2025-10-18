<?php
declare(strict_types=1);

namespace App\Controllers;

use App\{DB, Logger, DatabaseService, Cache};

/**
 * Contest management controller
 */
class ContestController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();
        $this->requireOrganizer();
        
        $pagination = $this->getPaginationParams();
        $searchParams = $this->getSearchParams();
        
        $contests = PaginationService::getContests($pagination['page'], $pagination['per_page']);
        
        $this->renderView('contests/index', [
            'contests' => $contests['items'],
            'pagination' => $contests['pagination'],
            'search' => $searchParams
        ]);
    }

    public function show(string $id): void
    {
        $this->requireAuth();
        $this->requireOrganizer();
        
        $contest = DatabaseService::getContestWithDetails($id);
        
        if (!$contest) {
            $this->handleError('Contest not found', 404);
            return;
        }
        
        $this->renderView('contests/show', compact('contest'));
    }

    public function create(): void
    {
        $this->requireOrganizer();
        $this->renderView('contests/new');
    }

    public function store(): void
    {
        $this->requireOrganizer();
        $this->validateCsrf();
        
        $inputData = $this->sanitizeInput($_POST);
        $name = $inputData['name'] ?? '';
        $startDate = $inputData['start_date'] ?? '';
        $endDate = $inputData['end_date'] ?? '';
        
        $this->logDebug('contest_creation_attempt', 'contest', null, 
            "Attempting to create contest: name={$name}, start_date={$startDate}, end_date={$endDate}");
        
        try {
            $contestId = $this->generateUuid();
            $stmt = DB::pdo()->prepare('INSERT INTO contests (id, name, start_date, end_date) VALUES (?, ?, ?, ?)');
            $stmt->execute([$contestId, $name, $startDate, $endDate]);
            
            $this->logAction('contest_created', 'contest', $contestId, 
                "Contest created: {$name} ({$startDate} to {$endDate})");
            
            // Clear cache
            Cache::forget("contests_page_*");
            
            $this->redirectWithMessage('/contests', 'Contest created successfully');
            
        } catch (\PDOException $e) {
            $this->handleDatabaseError($e, 'contest_creation');
        } catch (\Exception $e) {
            $this->handleError('Failed to create contest: ' . $e->getMessage());
        }
    }

    public function edit(string $id): void
    {
        $this->requireOrganizer();
        
        $stmt = DB::pdo()->prepare('SELECT * FROM contests WHERE id = ?');
        $stmt->execute([$id]);
        $contest = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$contest) {
            $this->handleError('Contest not found', 404);
            return;
        }
        
        $this->renderView('contests/edit', compact('contest'));
    }

    public function update(string $id): void
    {
        $this->requireOrganizer();
        $this->validateCsrf();
        
        $inputData = $this->sanitizeInput($_POST);
        $name = $inputData['name'] ?? '';
        $startDate = $inputData['start_date'] ?? '';
        $endDate = $inputData['end_date'] ?? '';
        
        try {
            $stmt = DB::pdo()->prepare('UPDATE contests SET name = ?, start_date = ?, end_date = ? WHERE id = ?');
            $stmt->execute([$name, $startDate, $endDate, $id]);
            
            $this->logAction('contest_updated', 'contest', $id, 
                "Contest updated: {$name}");
            
            // Clear cache
            DatabaseService::clearContestCache($id);
            
            $this->redirectWithMessage('/contests', 'Contest updated successfully');
            
        } catch (\PDOException $e) {
            $this->handleDatabaseError($e, 'contest_update');
        } catch (\Exception $e) {
            $this->handleError('Failed to update contest: ' . $e->getMessage());
        }
    }

    public function destroy(string $id): void
    {
        $this->requireOrganizer();
        $this->validateCsrf();
        
        try {
            // Check if contest has categories
            $stmt = DB::pdo()->prepare('SELECT COUNT(*) FROM categories WHERE contest_id = ?');
            $stmt->execute([$id]);
            $categoryCount = $stmt->fetchColumn();
            
            if ($categoryCount > 0) {
                $this->redirectWithMessage('/contests', 'Cannot delete contest with categories. Please remove categories first.', 'error');
                return;
            }
            
            $stmt = DB::pdo()->prepare('DELETE FROM contests WHERE id = ?');
            $stmt->execute([$id]);
            
            $this->logAction('contest_deleted', 'contest', $id, 'Contest deleted');
            
            // Clear cache
            DatabaseService::clearContestCache($id);
            
            $this->redirectWithMessage('/contests', 'Contest deleted successfully');
            
        } catch (\PDOException $e) {
            $this->handleDatabaseError($e, 'contest_deletion');
        } catch (\Exception $e) {
            $this->handleError('Failed to delete contest: ' . $e->getMessage());
        }
    }

    public function archive(string $id): void
    {
        $this->requireOrganizer();
        $this->validateCsrf();
        
        try {
            $stmt = DB::pdo()->prepare('UPDATE contests SET archived = 1 WHERE id = ?');
            $stmt->execute([$id]);
            
            $this->logAction('contest_archived', 'contest', $id, 'Contest archived');
            
            // Clear cache
            DatabaseService::clearContestCache($id);
            
            $this->redirectWithMessage('/contests', 'Contest archived successfully');
            
        } catch (\PDOException $e) {
            $this->handleDatabaseError($e, 'contest_archival');
        } catch (\Exception $e) {
            $this->handleError('Failed to archive contest: ' . $e->getMessage());
        }
    }
}