<?php
declare(strict_types=1);
namespace App\Routes;
use App\DB;
use function App\{view, render, redirect, param, post, request_array, current_user, is_logged_in, is_organizer, is_judge, is_emcee, require_login, require_organizer, require_emcee};

function uuid(): string { return bin2hex(random_bytes(16)); }

class HomeController {
	public function index(): void { view('home', ['title' => 'Contest Judge']); }
	public function health(): void { header('Content-Type: application/json'); echo json_encode(['ok'=>true]); }
}

class ContestController {
	public function index(): void {
		require_login();
		if (!is_organizer()) { http_response_code(403); echo 'Forbidden'; return; }
		$rows = DB::pdo()->query('SELECT * FROM contests ORDER BY start_date DESC')->fetchAll(\PDO::FETCH_ASSOC);
		view('contests/index', compact('rows'));
	}
	public function new(): void { require_organizer(); view('contests/new'); }
	public function create(): void {
		require_organizer();
		
		$name = post('name');
		$startDate = post('start_date');
		$endDate = post('end_date');
		
		// Debug log contest creation attempt
		\App\Logger::debug('contest_creation_attempt', 'contest', null, 
			"Attempting to create contest: name={$name}, start_date={$startDate}, end_date={$endDate}");
		
		try {
			$contestId = uuid();
			$stmt = DB::pdo()->prepare('INSERT INTO contests (id, name, start_date, end_date) VALUES (?, ?, ?, ?)');
			$stmt->execute([$contestId, $name, $startDate, $endDate]);
			
			// Log successful outcome
			\App\Logger::debug('contest_creation_success', 'contest', $contestId, 
				"Contest created successfully: contest_id={$contestId}, name={$name}");
			\App\Logger::logAdminAction('contest_created', 'contest', $contestId, 
				"Contest created: {$name} ({$startDate} to {$endDate})");
			
			redirect('/contests');
		} catch (\Exception $e) {
			// Log failure outcome
			\App\Logger::debug('contest_creation_failed', 'contest', null, 
				"Contest creation failed: " . $e->getMessage());
			\App\Logger::error('contest_creation_failed', 'contest', null, 
				"Contest creation failed: " . $e->getMessage());
			
			redirect('/contests?error=creation_failed');
		}
	}
	
	public function archive(array $params): void {
		require_organizer();
		$contestId = param('id', $params);
		
		// Debug log archiving attempt
		\App\Logger::debug('contest_archiving_attempt', 'contest', $contestId, 
			"Attempting to archive contest: contest_id={$contestId}");
		
		// Get contest information
		$stmt = DB::pdo()->prepare('SELECT * FROM contests WHERE id = ?');
		$stmt->execute([$contestId]);
		$contest = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$contest) {
			\App\Logger::debug('contest_archiving_failed', 'contest', $contestId, 
				"Contest archiving failed: contest not found");
			redirect('/contests?error=contest_not_found');
			return;
		}
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			$archivedBy = $_SESSION['user']['name'] ?? 'Unknown';
			$archivedContestId = uuid();
			
			// Archive the contest
			$stmt = $pdo->prepare('INSERT INTO archived_contests (id, name, description, start_date, end_date, archived_by) VALUES (?, ?, ?, ?, ?, ?)');
			$stmt->execute([$archivedContestId, $contest['name'], $contest['description'] ?? null, $contest['start_date'], $contest['end_date'], $archivedBy]);
			\App\Logger::debug('contest_archived', 'contest', $archivedContestId, 
				"Contest archived: contest_id={$contestId}, archived_id={$archivedContestId}, name={$contest['name']}");
			
			// Get all categories for this contest
			$stmt = $pdo->prepare('SELECT * FROM categories WHERE contest_id = ?');
			$stmt->execute([$contestId]);
			$categories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			foreach ($categories as $category) {
				$archivedCategoryId = uuid();
				
				// Archive the category
				$stmt = $pdo->prepare('INSERT INTO archived_categories (id, archived_contest_id, name, description) VALUES (?, ?, ?, ?)');
				$stmt->execute([$archivedCategoryId, $archivedContestId, $category['name'], $category['description'] ?? null]);
				
				// Get all subcategories for this category
				$stmt = $pdo->prepare('SELECT * FROM subcategories WHERE category_id = ?');
				$stmt->execute([$category['id']]);
				$subcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				
				foreach ($subcategories as $subcategory) {
					$archivedSubcategoryId = uuid();
					
					// Archive the subcategory
					$stmt = $pdo->prepare('INSERT INTO archived_subcategories (id, archived_category_id, name, description, score_cap) VALUES (?, ?, ?, ?, ?)');
					$stmt->execute([$archivedSubcategoryId, $archivedCategoryId, $subcategory['name'], $subcategory['description'] ?? null, $subcategory['score_cap']]);
					
					// Archive criteria
					$stmt = $pdo->prepare('SELECT * FROM criteria WHERE subcategory_id = ?');
					$stmt->execute([$subcategory['id']]);
					$criteria = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($criteria as $criterion) {
						$archivedCriterionId = uuid();
						$stmt = $pdo->prepare('INSERT INTO archived_criteria (id, archived_subcategory_id, name, max_score) VALUES (?, ?, ?, ?)');
						$stmt->execute([$archivedCriterionId, $archivedSubcategoryId, $criterion['name'], $criterion['max_score']]);
					}
					
					// Archive scores
					$stmt = $pdo->prepare('SELECT s.*, c.id as criterion_id FROM scores s JOIN criteria c ON s.criterion_id = c.id WHERE s.subcategory_id = ?');
					$stmt->execute([$subcategory['id']]);
					$scores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($scores as $score) {
						$archivedScoreId = uuid();
						$stmt = $pdo->prepare('INSERT INTO archived_scores (id, archived_subcategory_id, archived_contestant_id, archived_judge_id, archived_criterion_id, score) VALUES (?, ?, ?, ?, ?, ?)');
						$stmt->execute([$archivedScoreId, $archivedSubcategoryId, $score['contestant_id'], $score['judge_id'], $score['criterion_id'], $score['score']]);
					}
					
					// Archive judge comments
					$stmt = $pdo->prepare('SELECT * FROM judge_comments WHERE subcategory_id = ?');
					$stmt->execute([$subcategory['id']]);
					$comments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($comments as $comment) {
						$archivedCommentId = uuid();
						$stmt = $pdo->prepare('INSERT INTO archived_judge_comments (id, archived_subcategory_id, archived_contestant_id, archived_judge_id, comment) VALUES (?, ?, ?, ?, ?)');
						$stmt->execute([$archivedCommentId, $archivedSubcategoryId, $comment['contestant_id'], $comment['judge_id'], $comment['comment']]);
					}
					
					// Archive judge certifications
					$stmt = $pdo->prepare('SELECT * FROM judge_certifications WHERE subcategory_id = ?');
					$stmt->execute([$subcategory['id']]);
					$certifications = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($certifications as $cert) {
						$archivedCertId = uuid();
						$stmt = $pdo->prepare('INSERT INTO archived_judge_certifications (id, archived_subcategory_id, archived_judge_id, signature_name, certified_at) VALUES (?, ?, ?, ?, ?)');
						$stmt->execute([$archivedCertId, $archivedSubcategoryId, $cert['judge_id'], $cert['signature_name'], $cert['certified_at']]);
					}
					
					// Archive overall deductions
					$stmt = $pdo->prepare('SELECT * FROM overall_deductions WHERE subcategory_id = ?');
					$stmt->execute([$subcategory['id']]);
					$deductions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($deductions as $deduction) {
						$archivedDeductionId = uuid();
						$stmt = $pdo->prepare('INSERT INTO archived_overall_deductions (id, archived_subcategory_id, archived_contestant_id, amount, comment, created_by, created_at, signature_name, signed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
						$stmt->execute([$archivedDeductionId, $archivedSubcategoryId, $deduction['contestant_id'], $deduction['amount'], $deduction['comment'], $deduction['created_by'], $deduction['created_at'], $deduction['signature_name'], $deduction['signed_at']]);
					}
					
					// Archive subcategory assignments
					$stmt = $pdo->prepare('SELECT * FROM subcategory_contestants WHERE subcategory_id = ?');
					$stmt->execute([$subcategory['id']]);
					$subcategoryContestants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($subcategoryContestants as $assignment) {
						$stmt = $pdo->prepare('INSERT INTO archived_subcategory_contestants (archived_subcategory_id, archived_contestant_id) VALUES (?, ?)');
						$stmt->execute([$archivedSubcategoryId, $assignment['contestant_id']]);
					}
					
					$stmt = $pdo->prepare('SELECT * FROM subcategory_judges WHERE subcategory_id = ?');
					$stmt->execute([$subcategory['id']]);
					$subcategoryJudges = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($subcategoryJudges as $assignment) {
						$stmt = $pdo->prepare('INSERT INTO archived_subcategory_judges (archived_subcategory_id, archived_judge_id) VALUES (?, ?)');
						$stmt->execute([$archivedSubcategoryId, $assignment['judge_id']]);
					}
				}
				
				// Archive category assignments
				$stmt = $pdo->prepare('SELECT * FROM category_contestants WHERE category_id = ?');
				$stmt->execute([$category['id']]);
				$categoryContestants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				
				foreach ($categoryContestants as $assignment) {
					$stmt = $pdo->prepare('INSERT INTO archived_category_contestants (archived_category_id, archived_contestant_id) VALUES (?, ?)');
					$stmt->execute([$archivedCategoryId, $assignment['contestant_id']]);
				}
				
				$stmt = $pdo->prepare('SELECT * FROM category_judges WHERE category_id = ?');
				$stmt->execute([$category['id']]);
				$categoryJudges = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				
				foreach ($categoryJudges as $assignment) {
					$stmt = $pdo->prepare('INSERT INTO archived_category_judges (archived_category_id, archived_judge_id) VALUES (?, ?)');
					$stmt->execute([$archivedCategoryId, $assignment['judge_id']]);
				}
			}
			
			// Archive contestants and judges (only if they're not used in other contests)
			$stmt = $pdo->prepare('SELECT DISTINCT c.* FROM contestants c JOIN subcategory_contestants sc ON c.id = sc.contestant_id JOIN subcategories s ON sc.subcategory_id = s.id JOIN categories cat ON s.category_id = cat.id WHERE cat.contest_id = ?');
			$stmt->execute([$contestId]);
			$contestants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			foreach ($contestants as $contestant) {
				// Check if contestant is used in other contests
				$stmt = $pdo->prepare('SELECT COUNT(*) FROM subcategory_contestants sc JOIN subcategories s ON sc.subcategory_id = s.id JOIN categories cat ON s.category_id = cat.id WHERE sc.contestant_id = ? AND cat.contest_id != ?');
				$stmt->execute([$contestant['id'], $contestId]);
				$otherContests = $stmt->fetchColumn();
				
				if ($otherContests == 0) {
					$stmt = $pdo->prepare('INSERT INTO archived_contestants (id, name, email, gender, contestant_number, bio, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
					$stmt->execute([$contestant['id'], $contestant['name'], $contestant['email'], $contestant['gender'], $contestant['contestant_number'], $contestant['bio'], $contestant['image_path']]);
				}
			}
			
			$stmt = $pdo->prepare('SELECT DISTINCT j.* FROM judges j JOIN subcategory_judges sj ON j.id = sj.judge_id JOIN subcategories s ON sj.subcategory_id = s.id JOIN categories cat ON s.category_id = cat.id WHERE cat.contest_id = ?');
			$stmt->execute([$contestId]);
			$judges = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			foreach ($judges as $judge) {
				// Check if judge is used in other contests
				$stmt = $pdo->prepare('SELECT COUNT(*) FROM subcategory_judges sj JOIN subcategories s ON sj.subcategory_id = s.id JOIN categories cat ON s.category_id = cat.id WHERE sj.judge_id = ? AND cat.contest_id != ?');
				$stmt->execute([$judge['id'], $contestId]);
				$otherContests = $stmt->fetchColumn();
				
				if ($otherContests == 0) {
					$stmt = $pdo->prepare('INSERT INTO archived_judges (id, name, email, gender, bio, image_path) VALUES (?, ?, ?, ?, ?, ?)');
					$stmt->execute([$judge['id'], $judge['name'], $judge['email'], $judge['gender'], $judge['bio'], $judge['image_path']]);
				}
			}
			
			// Delete the original contest and all associated data
			$pdo->prepare('DELETE FROM contests WHERE id = ?')->execute([$contestId]);
			
			$pdo->commit();
			
			// Log successful outcome
			\App\Logger::debug('contest_archiving_success', 'contest', $archivedContestId, 
				"Contest archiving completed successfully: contest_id={$contestId}, archived_id={$archivedContestId}, name={$contest['name']}");
			\App\Logger::logContestArchive($contestId, $contest['name']);
			
			redirect('/contests?success=contest_archived');
		} catch (\Exception $e) {
			$pdo->rollBack();
			
			// Log failure outcome
			\App\Logger::debug('contest_archiving_failed', 'contest', $contestId, 
				"Contest archiving failed: " . $e->getMessage());
			\App\Logger::error('contest_archiving_failed', 'contest', $contestId, 
				"Contest archiving failed: " . $e->getMessage());
			
			redirect('/contests?error=archive_failed');
		}
	}
	
	public function archivedContests(): void {
		require_organizer();
		$rows = DB::pdo()->query('SELECT * FROM archived_contests ORDER BY archived_at DESC')->fetchAll(\PDO::FETCH_ASSOC);
		view('contests/archived', compact('rows'));
	}
	
	public function archivedContestDetails(array $params): void {
		require_organizer();
		$contestId = param('id', $params);
		
		// Get archived contest details
		$stmt = DB::pdo()->prepare('SELECT * FROM archived_contests WHERE id = ?');
		$stmt->execute([$contestId]);
		$contest = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$contest) {
			http_response_code(404);
			echo 'Archived contest not found';
			return;
		}
		
		// Get archived categories for this contest
		$stmt = DB::pdo()->prepare('SELECT * FROM archived_categories WHERE archived_contest_id = ? ORDER BY name');
		$stmt->execute([$contestId]);
		$categories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get archived subcategories
		$stmt = DB::pdo()->prepare('SELECT sc.*, c.name as category_name FROM archived_subcategories sc JOIN archived_categories c ON sc.archived_category_id = c.id WHERE c.archived_contest_id = ? ORDER BY c.name, sc.name');
		$stmt->execute([$contestId]);
		$subcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get archived contestants
		$stmt = DB::pdo()->prepare('SELECT * FROM archived_contestants ORDER BY contestant_number IS NULL, contestant_number, name');
		$stmt->execute();
		$contestants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get archived judges
		$stmt = DB::pdo()->prepare('SELECT * FROM archived_judges ORDER BY name');
		$stmt->execute();
		$judges = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Calculate category winners
		$categoryWinners = [];
		foreach ($categories as $category) {
			// Get all subcategories for this category
			$stmt = DB::pdo()->prepare('SELECT id FROM archived_subcategories WHERE archived_category_id = ?');
			$stmt->execute([$category['id']]);
			$subcategoryIds = array_column($stmt->fetchAll(\PDO::FETCH_ASSOC), 'id');
			
			if (!empty($subcategoryIds)) {
				// Calculate total scores for each contestant in this category
				$placeholders = str_repeat('?,', count($subcategoryIds) - 1) . '?';
				$stmt = DB::pdo()->prepare("
					SELECT 
						ac.id as contestant_id,
						ac.name as contestant_name,
						ac.contestant_number,
						SUM(s.score) as total_score,
						COUNT(s.score) as score_count
					FROM archived_contestants ac
					LEFT JOIN archived_scores s ON ac.id = s.archived_contestant_id AND s.archived_subcategory_id IN ($placeholders)
					GROUP BY ac.id, ac.name, ac.contestant_number
					HAVING score_count > 0
					ORDER BY total_score DESC
					LIMIT 1
				");
				$stmt->execute($subcategoryIds);
				$winner = $stmt->fetch(\PDO::FETCH_ASSOC);
				
				if ($winner) {
					$categoryWinners[$category['id']] = $winner;
				}
			}
		}
		
		view('contests/archived_details', compact('contest', 'categories', 'subcategories', 'contestants', 'judges', 'categoryWinners'));
	}
	
	public function archivedContestPrint(array $params): void {
		require_organizer();
		$contestId = param('id', $params);
		
		// Get archived contest details
		$stmt = DB::pdo()->prepare('SELECT * FROM archived_contests WHERE id = ?');
		$stmt->execute([$contestId]);
		$contest = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$contest) {
			http_response_code(404);
			echo 'Archived contest not found';
			return;
		}
		
		// Get comprehensive score data
		$stmt = DB::pdo()->prepare('
			SELECT 
				c.name as category_name,
				sc.name as subcategory_name,
				ac.name as contestant_name,
				ac.contestant_number,
				aj.name as judge_name,
				cr.name as criterion_name,
				s.score,
				jc.comment,
				od.amount as deduction_amount,
				od.comment as deduction_comment
			FROM archived_categories c
			JOIN archived_subcategories sc ON c.id = sc.archived_category_id
			JOIN archived_contestants ac ON 1=1
			LEFT JOIN archived_scores s ON s.archived_subcategory_id = sc.id AND s.archived_contestant_id = ac.id
			LEFT JOIN archived_judges aj ON s.archived_judge_id = aj.id
			LEFT JOIN archived_criteria cr ON s.archived_criterion_id = cr.id
			LEFT JOIN archived_judge_comments jc ON jc.archived_subcategory_id = sc.id AND jc.archived_contestant_id = ac.id AND jc.archived_judge_id = aj.id
			LEFT JOIN archived_overall_deductions od ON od.archived_subcategory_id = sc.id AND od.archived_contestant_id = ac.id
			WHERE c.archived_contest_id = ?
			ORDER BY c.name, sc.name, ac.contestant_number, ac.name, aj.name, cr.name
		');
		$stmt->execute([$contestId]);
		$scoreData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Organize data by category and subcategory
		$organizedData = [];
		foreach ($scoreData as $row) {
			$categoryName = $row['category_name'];
			$subcategoryName = $row['subcategory_name'];
			$contestantName = $row['contestant_name'];
			$contestantNumber = $row['contestant_number'];
			
			if (!isset($organizedData[$categoryName])) {
				$organizedData[$categoryName] = [];
			}
			if (!isset($organizedData[$categoryName][$subcategoryName])) {
				$organizedData[$categoryName][$subcategoryName] = [];
			}
			if (!isset($organizedData[$categoryName][$subcategoryName][$contestantName])) {
				$organizedData[$categoryName][$subcategoryName][$contestantName] = [
					'contestant_number' => $contestantNumber,
					'scores' => [],
					'comments' => [],
					'deductions' => []
				];
			}
			
			if ($row['score'] !== null) {
				$organizedData[$categoryName][$subcategoryName][$contestantName]['scores'][] = [
					'judge' => $row['judge_name'],
					'criterion' => $row['criterion_name'],
					'score' => $row['score']
				];
			}
			
			if ($row['comment']) {
				$organizedData[$categoryName][$subcategoryName][$contestantName]['comments'][] = [
					'judge' => $row['judge_name'],
					'comment' => $row['comment']
				];
			}
			
			if ($row['deduction_amount'] !== null) {
				$organizedData[$categoryName][$subcategoryName][$contestantName]['deductions'][] = [
					'amount' => $row['deduction_amount'],
					'comment' => $row['deduction_comment']
				];
			}
		}
		
		// Calculate totals and rankings
		$categoryTotals = [];
		foreach ($organizedData as $categoryName => $subcategories) {
			$categoryTotals[$categoryName] = [];
			foreach ($subcategories as $subcategoryName => $contestants) {
				foreach ($contestants as $contestantName => $data) {
					$totalScore = 0;
					foreach ($data['scores'] as $score) {
						$totalScore += $score['score'];
					}
					
					// Subtract deductions
					foreach ($data['deductions'] as $deduction) {
						$totalScore -= $deduction['amount'];
					}
					
					if (!isset($categoryTotals[$categoryName][$contestantName])) {
						$categoryTotals[$categoryName][$contestantName] = [
							'contestant_number' => $data['contestant_number'],
							'total_score' => 0
						];
					}
					$categoryTotals[$categoryName][$contestantName]['total_score'] += $totalScore;
				}
			}
			
			// Sort by total score descending
			uasort($categoryTotals[$categoryName], function($a, $b) {
				return $b['total_score'] <=> $a['total_score'];
			});
		}
		
		view('contests/archived_print', compact('contest', 'organizedData', 'categoryTotals'));
	}
	
	public function reactivateContest(array $params): void {
		require_organizer();
		$archivedContestId = param('id', $params);
		
		\App\Logger::debug('contest_reactivation_attempt', 'contest', $archivedContestId, 
			"Attempting to reactivate contest: archived_contest_id={$archivedContestId}");
		
		// Get archived contest details
		$stmt = DB::pdo()->prepare('SELECT * FROM archived_contests WHERE id = ?');
		$stmt->execute([$archivedContestId]);
		$archivedContest = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$archivedContest) {
			\App\Logger::debug('contest_reactivation_failed', 'contest', $archivedContestId, 
				"Contest reactivation failed: archived contest not found");
			redirect('/admin/archived-contests?error=contest_not_found');
			return;
		}
		
		\App\Logger::debug('contest_reactivation_data', 'contest', $archivedContestId, 
			"Found archived contest: " . json_encode($archivedContest));
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			$reactivatedBy = $_SESSION['user']['name'] ?? 'Unknown';
			$newContestId = uuid();
			
			\App\Logger::debug('contest_reactivation_create', 'contest', $newContestId, 
				"Creating new contest: new_contest_id={$newContestId}, name={$archivedContest['name']}");
			
			// Create new contest
			$stmt = $pdo->prepare('INSERT INTO contests (id, name, start_date, end_date) VALUES (?, ?, ?, ?)');
			$stmt->execute([$newContestId, $archivedContest['name'], $archivedContest['start_date'], $archivedContest['end_date']]);
			
			// Get all archived categories for this contest
			$stmt = $pdo->prepare('SELECT * FROM archived_categories WHERE archived_contest_id = ?');
			$stmt->execute([$archivedContestId]);
			$archivedCategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			\App\Logger::debug('contest_reactivation_categories', 'contest', $newContestId, 
				"Found " . count($archivedCategories) . " archived categories to restore");
			
			foreach ($archivedCategories as $archivedCategory) {
				$newCategoryId = uuid();
				
				\App\Logger::debug('contest_reactivation_category', 'contest', $newContestId, 
					"Restoring category: archived_id={$archivedCategory['id']}, new_id={$newCategoryId}, name={$archivedCategory['name']}");
				
				// Create new category
				$stmt = $pdo->prepare('INSERT INTO categories (id, contest_id, name) VALUES (?, ?, ?)');
				$stmt->execute([$newCategoryId, $newContestId, $archivedCategory['name']]);
				
				// Get all archived subcategories for this category
				$stmt = $pdo->prepare('SELECT * FROM archived_subcategories WHERE archived_category_id = ?');
				$stmt->execute([$archivedCategory['id']]);
				$archivedSubcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				
				\App\Logger::debug('contest_reactivation_subcategories', 'contest', $newContestId, 
					"Found " . count($archivedSubcategories) . " archived subcategories for category {$archivedCategory['name']}");
				
				foreach ($archivedSubcategories as $archivedSubcategory) {
					$newSubcategoryId = uuid();
					
					// Create new subcategory
					$stmt = $pdo->prepare('INSERT INTO subcategories (id, category_id, name, description, score_cap) VALUES (?, ?, ?, ?, ?)');
					$stmt->execute([$newSubcategoryId, $newCategoryId, $archivedSubcategory['name'], $archivedSubcategory['description'], $archivedSubcategory['score_cap']]);
					
					// Restore criteria
					$stmt = $pdo->prepare('SELECT * FROM archived_criteria WHERE archived_subcategory_id = ?');
					$stmt->execute([$archivedSubcategory['id']]);
					$archivedCriteria = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($archivedCriteria as $archivedCriterion) {
						$newCriterionId = uuid();
						$stmt = $pdo->prepare('INSERT INTO criteria (id, subcategory_id, name, max_score) VALUES (?, ?, ?, ?)');
						$stmt->execute([$newCriterionId, $newSubcategoryId, $archivedCriterion['name'], $archivedCriterion['max_score']]);
					}
					
					// Restore scores
					$stmt = $pdo->prepare('SELECT * FROM archived_scores WHERE archived_subcategory_id = ?');
					$stmt->execute([$archivedSubcategory['id']]);
					$archivedScores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($archivedScores as $archivedScore) {
						$newScoreId = uuid();
						$stmt = $pdo->prepare('INSERT INTO scores (id, subcategory_id, contestant_id, judge_id, criterion_id, score) VALUES (?, ?, ?, ?, ?, ?)');
						$stmt->execute([$newScoreId, $newSubcategoryId, $archivedScore['archived_contestant_id'], $archivedScore['archived_judge_id'], $archivedScore['archived_criterion_id'], $archivedScore['score']]);
					}
					
					// Restore judge comments
					$stmt = $pdo->prepare('SELECT * FROM archived_judge_comments WHERE archived_subcategory_id = ?');
					$stmt->execute([$archivedSubcategory['id']]);
					$archivedComments = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($archivedComments as $archivedComment) {
						$newCommentId = uuid();
						$stmt = $pdo->prepare('INSERT INTO judge_comments (id, subcategory_id, contestant_id, judge_id, comment) VALUES (?, ?, ?, ?, ?)');
						$stmt->execute([$newCommentId, $newSubcategoryId, $archivedComment['archived_contestant_id'], $archivedComment['archived_judge_id'], $archivedComment['comment']]);
					}
					
					// Restore judge certifications
					$stmt = $pdo->prepare('SELECT * FROM archived_judge_certifications WHERE archived_subcategory_id = ?');
					$stmt->execute([$archivedSubcategory['id']]);
					$archivedCertifications = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($archivedCertifications as $archivedCert) {
						$newCertId = uuid();
						$stmt = $pdo->prepare('INSERT INTO judge_certifications (id, subcategory_id, judge_id, signature_name, certified_at) VALUES (?, ?, ?, ?, ?)');
						$stmt->execute([$newCertId, $newSubcategoryId, $archivedCert['archived_judge_id'], $archivedCert['signature_name'], $archivedCert['certified_at']]);
					}
					
					// Restore overall deductions
					$stmt = $pdo->prepare('SELECT * FROM archived_overall_deductions WHERE archived_subcategory_id = ?');
					$stmt->execute([$archivedSubcategory['id']]);
					$archivedDeductions = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($archivedDeductions as $archivedDeduction) {
						$newDeductionId = uuid();
						$stmt = $pdo->prepare('INSERT INTO overall_deductions (id, subcategory_id, contestant_id, amount, comment, created_by, created_at, signature_name, signed_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
						$stmt->execute([$newDeductionId, $newSubcategoryId, $archivedDeduction['archived_contestant_id'], $archivedDeduction['amount'], $archivedDeduction['comment'], $archivedDeduction['created_by'], $archivedDeduction['created_at'], $archivedDeduction['signature_name'], $archivedDeduction['signed_at']]);
					}
					
					// Restore subcategory assignments
					$stmt = $pdo->prepare('SELECT * FROM archived_subcategory_contestants WHERE archived_subcategory_id = ?');
					$stmt->execute([$archivedSubcategory['id']]);
					$archivedSubcategoryContestants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($archivedSubcategoryContestants as $assignment) {
						$stmt = $pdo->prepare('INSERT INTO subcategory_contestants (subcategory_id, contestant_id) VALUES (?, ?)');
						$stmt->execute([$newSubcategoryId, $assignment['archived_contestant_id']]);
					}
					
					$stmt = $pdo->prepare('SELECT * FROM archived_subcategory_judges WHERE archived_subcategory_id = ?');
					$stmt->execute([$archivedSubcategory['id']]);
					$archivedSubcategoryJudges = $stmt->fetchAll(\PDO::FETCH_ASSOC);
					
					foreach ($archivedSubcategoryJudges as $assignment) {
						$stmt = $pdo->prepare('INSERT INTO subcategory_judges (subcategory_id, judge_id) VALUES (?, ?)');
						$stmt->execute([$newSubcategoryId, $assignment['archived_judge_id']]);
					}
				}
				
				// Restore category assignments
				$stmt = $pdo->prepare('SELECT * FROM archived_category_contestants WHERE archived_category_id = ?');
				$stmt->execute([$archivedCategory['id']]);
				$archivedCategoryContestants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				
				foreach ($archivedCategoryContestants as $assignment) {
					$stmt = $pdo->prepare('INSERT INTO category_contestants (category_id, contestant_id) VALUES (?, ?)');
					$stmt->execute([$newCategoryId, $assignment['archived_contestant_id']]);
				}
				
				$stmt = $pdo->prepare('SELECT * FROM archived_category_judges WHERE archived_category_id = ?');
				$stmt->execute([$archivedCategory['id']]);
				$archivedCategoryJudges = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				
				foreach ($archivedCategoryJudges as $assignment) {
					$stmt = $pdo->prepare('INSERT INTO category_judges (category_id, judge_id) VALUES (?, ?)');
					$stmt->execute([$newCategoryId, $assignment['archived_judge_id']]);
				}
			}
			
			$pdo->commit();
			\App\Logger::debug('contest_reactivation_success', 'contest', $newContestId, 
				"Contest reactivation completed successfully: new_contest_id={$newContestId}");
			\App\Logger::logAdminAction('contest_reactivated', 'contest', $newContestId, "Contest '{$archivedContest['name']}' reactivated from archive by {$reactivatedBy}");
			redirect('/contests?success=contest_reactivated');
		} catch (\Exception $e) {
			$pdo->rollBack();
			\App\Logger::error('contest_reactivation_failed', 'contest', $archivedContestId ?? 'unknown', 
				"Contest reactivation failed: " . $e->getMessage() . " | Stack trace: " . $e->getTraceAsString());
			redirect('/admin/archived-contests?error=reactivation_failed&message=' . urlencode($e->getMessage()));
		}
	}
}

class BackupController {
	public function index(): void {
		require_organizer();
		
		// Refresh Logger to ensure we have the latest log level
		\App\Logger::refreshLevel();
		
		// Get backup logs
		$stmt = DB::pdo()->prepare('
			SELECT bl.*, u.preferred_name as created_by_name
			FROM backup_logs bl
			LEFT JOIN users u ON bl.created_by = u.id
			ORDER BY bl.created_at DESC
			LIMIT 50
		');
		$stmt->execute();
		$backupLogs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get backup settings
		$stmt = DB::pdo()->query('SELECT * FROM backup_settings ORDER BY backup_type');
		$backupSettings = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// If no backup settings exist, create defaults
		if (empty($backupSettings)) {
			$pdo = DB::pdo();
			
			// Ensure the table exists and has the correct structure
			$this->ensureBackupSettingsTable();
			
			$stmt = $pdo->prepare('INSERT INTO backup_settings (id, backup_type, enabled, frequency, frequency_value, retention_days) VALUES (?, ?, ?, ?, ?, ?)');
			$stmt->execute([uuid(), 'schema', 0, 'daily', 1, 30]);
			$stmt->execute([uuid(), 'full', 0, 'weekly', 1, 30]);
			
			// Re-fetch the settings
			$stmt = $pdo->query('SELECT * FROM backup_settings ORDER BY backup_type');
			$backupSettings = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			// Log that we created default settings
			\App\Logger::info('Created default backup settings: schema and full');
		}
		
		$backupDirectory = $this->getBackupDirectory();
		
		// Convert timestamps to ISO format for JavaScript compatibility
		foreach ($backupSettings as &$setting) {
			if ($setting['last_run']) {
				$setting['last_run'] = date('c', strtotime($setting['last_run']));
			}
			if ($setting['next_run']) {
				$setting['next_run'] = date('c', strtotime($setting['next_run']));
			}
		}
		
		foreach ($backupLogs as &$backup) {
			if ($backup['created_at']) {
				$backup['created_at'] = date('c', strtotime($backup['created_at']));
			}
		}
		
		view('admin/backups', compact('backupLogs', 'backupSettings', 'backupDirectory'));
	}
	
	public function createSchemaBackup(): void {
		require_organizer();
		
		try {
			$backupId = uuid();
			$timestamp = date('Y-m-d_H-i-s');
			$backupDir = $this->getBackupDirectory();
			
			$fileName = "schema_backup_{$timestamp}.sql";
			$filePath = $backupDir . '/' . $fileName;
			
			// Log backup start
			$stmt = DB::pdo()->prepare('INSERT INTO backup_logs (id, backup_type, file_path, file_size, status, created_by) VALUES (?, ?, ?, ?, ?, ?)');
			$stmt->execute([$backupId, 'schema', $filePath, 0, 'in_progress', $_SESSION['user']['id']]);
			
			// Get database schema
			$schema = $this->getDatabaseSchema();
			
			// Write schema to file
			file_put_contents($filePath, $schema);
			$fileSize = filesize($filePath);
			
			// Update backup log
			$stmt = DB::pdo()->prepare('UPDATE backup_logs SET file_size = ?, status = ? WHERE id = ?');
			$stmt->execute([$fileSize, 'success', $backupId]);
			
			\App\Logger::logAdminAction('schema_backup_created', 'backup', $backupId, "Schema backup created: {$fileName}");
			
			redirect('/admin/backups?success=schema_backup_created');
		} catch (\Exception $e) {
			// Update backup log with error
			if (isset($backupId)) {
				$stmt = DB::pdo()->prepare('UPDATE backup_logs SET status = ?, error_message = ? WHERE id = ?');
				$stmt->execute(['failed', $e->getMessage(), $backupId]);
			}
			
			redirect('/admin/backups?error=schema_backup_failed');
		}
	}
	
	public function createFullBackup(): void {
		require_organizer();
		
		try {
			$backupId = uuid();
			$timestamp = date('Y-m-d_H-i-s');
			$backupDir = $this->getBackupDirectory();
			
			$fileName = "full_backup_{$timestamp}.db";
			$filePath = $backupDir . '/' . $fileName;
			
			// Log backup start
			$stmt = DB::pdo()->prepare('INSERT INTO backup_logs (id, backup_type, file_path, file_size, status, created_by) VALUES (?, ?, ?, ?, ?, ?)');
			$stmt->execute([$backupId, 'full', $filePath, 0, 'in_progress', $_SESSION['user']['id']]);
			
			// Copy database file
			$dbPath = $this->getDatabasePath();
			if (!copy($dbPath, $filePath)) {
				throw new \Exception('Failed to copy database file from ' . $dbPath . ' to ' . $filePath);
			}
			
			$fileSize = filesize($filePath);
			
			// Update backup log
			$stmt = DB::pdo()->prepare('UPDATE backup_logs SET file_size = ?, status = ? WHERE id = ?');
			$stmt->execute([$fileSize, 'success', $backupId]);
			
			\App\Logger::logAdminAction('full_backup_created', 'backup', $backupId, "Full backup created: {$fileName}");
			
			redirect('/admin/backups?success=full_backup_created');
		} catch (\Exception $e) {
			// Update backup log with error
			if (isset($backupId)) {
				$stmt = DB::pdo()->prepare('UPDATE backup_logs SET status = ?, error_message = ? WHERE id = ?');
				$stmt->execute(['failed', $e->getMessage(), $backupId]);
			}
			
			redirect('/admin/backups?error=full_backup_failed');
		}
	}
	
	public function downloadBackup(array $params): void {
		require_organizer();
		$backupId = param('id', $params);
		
		$stmt = DB::pdo()->prepare('SELECT * FROM backup_logs WHERE id = ? AND status = ?');
		$stmt->execute([$backupId, 'success']);
		$backup = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$backup || !file_exists($backup['file_path'])) {
			redirect('/admin/backups?error=backup_not_found');
			return;
		}
		
		$fileName = basename($backup['file_path']);
		$fileSize = filesize($backup['file_path']);
		
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . $fileName . '"');
		header('Content-Length: ' . $fileSize);
		header('Cache-Control: no-cache, must-revalidate');
		
		readfile($backup['file_path']);
		exit;
	}
	
	public function deleteBackup(array $params): void {
		require_organizer();
		$backupId = param('id', $params);
		
		$stmt = DB::pdo()->prepare('SELECT * FROM backup_logs WHERE id = ?');
		$stmt->execute([$backupId]);
		$backup = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$backup) {
			redirect('/admin/backups?error=backup_not_found');
			return;
		}
		
		try {
			// Delete file if it exists
			if (file_exists($backup['file_path'])) {
				unlink($backup['file_path']);
			}
			
			// Delete from database
			$stmt = DB::pdo()->prepare('DELETE FROM backup_logs WHERE id = ?');
			$stmt->execute([$backupId]);
			
			\App\Logger::logAdminAction('backup_deleted', 'backup', $backupId, "Backup deleted: " . basename($backup['file_path']));
			
			redirect('/admin/backups?success=backup_deleted');
		} catch (\Exception $e) {
			redirect('/admin/backups?error=backup_delete_failed');
		}
	}
	
	public function updateSettings(): void {
		require_organizer();
		
		$schemaEnabled = isset($_POST['schema_enabled']) ? 1 : 0;
		$schemaFrequency = $_POST['schema_frequency'] ?? 'daily';
		$schemaFrequencyValue = (int)($_POST['schema_frequency_value'] ?? 1);
		$schemaRetention = (int)($_POST['schema_retention'] ?? 30);
		
		$fullEnabled = isset($_POST['full_enabled']) ? 1 : 0;
		$fullFrequency = $_POST['full_frequency'] ?? 'weekly';
		$fullFrequencyValue = (int)($_POST['full_frequency_value'] ?? 1);
		$fullRetention = (int)($_POST['full_retention'] ?? 30);
		
		try {
			$pdo = DB::pdo();
			$pdo->beginTransaction();
			
			// Update schema backup settings
			$schemaNextRun = $schemaEnabled ? $this->calculateNextRun($schemaFrequency, $schemaFrequencyValue) : null;
			
			$stmt = $pdo->prepare('UPDATE backup_settings SET enabled = ?, frequency = ?, frequency_value = ?, retention_days = ?, next_run = ?, updated_at = CURRENT_TIMESTAMP WHERE backup_type = ?');
			$result = $stmt->execute([$schemaEnabled, $schemaFrequency, $schemaFrequencyValue, $schemaRetention, $schemaNextRun, 'schema']);
			$rowsAffected = $stmt->rowCount();
			
			// If no rows were affected, the schema backup setting doesn't exist - create it
			if ($rowsAffected === 0) {
				$stmt = $pdo->prepare('INSERT INTO backup_settings (id, backup_type, enabled, frequency, frequency_value, retention_days, next_run) VALUES (?, ?, ?, ?, ?, ?, ?)');
				$stmt->execute([uuid(), 'schema', $schemaEnabled, $schemaFrequency, $schemaFrequencyValue, $schemaRetention, $schemaNextRun]);
			}
			
			// Update full backup settings
			$fullNextRun = $fullEnabled ? $this->calculateNextRun($fullFrequency, $fullFrequencyValue) : null;
			
			$stmt = $pdo->prepare('UPDATE backup_settings SET enabled = ?, frequency = ?, frequency_value = ?, retention_days = ?, next_run = ?, updated_at = CURRENT_TIMESTAMP WHERE backup_type = ?');
			$result = $stmt->execute([$fullEnabled, $fullFrequency, $fullFrequencyValue, $fullRetention, $fullNextRun, 'full']);
			$rowsAffected = $stmt->rowCount();
			
			// If no rows were affected, the full backup setting doesn't exist - create it
			if ($rowsAffected === 0) {
				$stmt = $pdo->prepare('INSERT INTO backup_settings (id, backup_type, enabled, frequency, frequency_value, retention_days, next_run) VALUES (?, ?, ?, ?, ?, ?, ?)');
				$stmt->execute([uuid(), 'full', $fullEnabled, $fullFrequency, $fullFrequencyValue, $fullRetention, $fullNextRun]);
			}
			
			$pdo->commit();
			
			\App\Logger::logAdminAction('backup_settings_updated', 'settings', null, 'Backup settings updated');
			
			redirect('/admin/backups?success=settings_updated');
		} catch (\Exception $e) {
			$pdo->rollBack();
			\App\Logger::error('Backup settings update failed: ' . $e->getMessage());
			redirect('/admin/backups?error=settings_update_failed');
		}
	}
	
	public function runScheduledBackups(): void {
		// This method can be called by cron or scheduled task
		// No authentication required as it's an internal process
		
		try {
			$pdo = DB::pdo();
			$now = date('Y-m-d H:i:s');
			$backupsRun = 0;
			$errors = [];
			
			// Get enabled backup settings
			$stmt = $pdo->query('SELECT * FROM backup_settings WHERE enabled = 1');
			$settings = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			foreach ($settings as $setting) {
				$shouldRun = false;
				
				// Check if it's time to run
				if (empty($setting['next_run'])) {
					$shouldRun = true;
				} else {
					// Use proper datetime comparison
					$nowTimestamp = strtotime($now);
					$nextRunTimestamp = strtotime($setting['next_run']);
					$shouldRun = $nowTimestamp >= $nextRunTimestamp;
				}
				
				if ($shouldRun) {
					try {
						$this->performScheduledBackup($setting);
						$backupsRun++;
						
						// Update last_run to current time and calculate next_run from current time
						$nextRun = $this->calculateNextRun($setting['frequency'], $setting['frequency_value'] ?? 1);
						$stmt = $pdo->prepare('UPDATE backup_settings SET last_run = ?, next_run = ? WHERE id = ?');
						$stmt->execute([$now, $nextRun, $setting['id']]);
					} catch (\Exception $e) {
						$errors[] = "Failed to run {$setting['backup_type']} backup: " . $e->getMessage();
					}
				}
			}
			
			// Clean up old backups
			$this->cleanupOldBackups();
			
			// If called via web browser, show results
			if (isset($_SERVER['HTTP_HOST'])) {
				$message = "Scheduled backups completed. {$backupsRun} backups run.";
				if (!empty($errors)) {
					$message .= " Errors: " . implode('; ', $errors);
				}
				redirect('/admin/backups?success=scheduled_backups_run&message=' . urlencode($message));
			}
			
		} catch (\Exception $e) {
			\App\Logger::error('Scheduled backup failed: ' . $e->getMessage());
			
			// If called via web browser, show error
			if (isset($_SERVER['HTTP_HOST'])) {
				redirect('/admin/backups?error=scheduled_backups_failed&message=' . urlencode($e->getMessage()));
			}
		}
	}
	
	private function performScheduledBackup(array $setting): void {
		$backupId = uuid();
		$timestamp = date('Y-m-d_H-i-s');
		$backupDir = $this->getBackupDirectory();
		
		try {
			if ($setting['backup_type'] === 'schema') {
				$fileName = "scheduled_schema_backup_{$timestamp}.sql";
				$filePath = $backupDir . '/' . $fileName;
				
				// Log backup start
				$stmt = DB::pdo()->prepare('INSERT INTO backup_logs (id, backup_type, file_path, file_size, status, created_by) VALUES (?, ?, ?, ?, ?, ?)');
				$stmt->execute([$backupId, 'scheduled', $filePath, 0, 'in_progress', null]);
				
				// Get database schema
				$schema = $this->getDatabaseSchema();
				
				// Write schema to file
				file_put_contents($filePath, $schema);
				$fileSize = filesize($filePath);
				
				// Update backup log
				$stmt = DB::pdo()->prepare('UPDATE backup_logs SET file_size = ?, status = ? WHERE id = ?');
				$stmt->execute([$fileSize, 'success', $backupId]);
				
			} else { // full backup
				$fileName = "scheduled_full_backup_{$timestamp}.db";
				$filePath = $backupDir . '/' . $fileName;
				
				// Log backup start
				$stmt = DB::pdo()->prepare('INSERT INTO backup_logs (id, backup_type, file_path, file_size, status, created_by) VALUES (?, ?, ?, ?, ?, ?)');
				$stmt->execute([$backupId, 'scheduled', $filePath, 0, 'in_progress', null]);
				
				// Copy database file
				$dbPath = $this->getDatabasePath();
				if (!copy($dbPath, $filePath)) {
					throw new \Exception('Failed to copy database file from ' . $dbPath . ' to ' . $filePath);
				}
				
				$fileSize = filesize($filePath);
				
				// Update backup log
				$stmt = DB::pdo()->prepare('UPDATE backup_logs SET file_size = ?, status = ? WHERE id = ?');
				$stmt->execute([$fileSize, 'success', $backupId]);
			}
			
			\App\Logger::info("Scheduled {$setting['backup_type']} backup completed: {$fileName}");
			
		} catch (\Exception $e) {
			// Update backup log with error
			$stmt = DB::pdo()->prepare('UPDATE backup_logs SET status = ?, error_message = ? WHERE id = ?');
			$stmt->execute(['failed', $e->getMessage(), $backupId]);
			
			\App\Logger::error("Scheduled {$setting['backup_type']} backup failed: " . $e->getMessage());
		}
	}
	
	private function calculateNextRun(string $frequency, int $frequencyValue = 1): string {
		switch ($frequency) {
			case 'minutes':
				return date('Y-m-d H:i:s', strtotime("+{$frequencyValue} minutes"));
			case 'hours':
				return date('Y-m-d H:i:s', strtotime("+{$frequencyValue} hours"));
			case 'daily':
				return date('Y-m-d H:i:s', strtotime("+{$frequencyValue} days"));
			case 'weekly':
				return date('Y-m-d H:i:s', strtotime("+{$frequencyValue} weeks"));
			case 'monthly':
				return date('Y-m-d H:i:s', strtotime("+{$frequencyValue} months"));
			default:
				return date('Y-m-d H:i:s', strtotime('+1 day'));
		}
	}
	
	private function cleanupOldBackups(): void {
		$pdo = DB::pdo();
		
		// Get retention settings
		$stmt = $pdo->query('SELECT backup_type, retention_days FROM backup_settings WHERE enabled = 1');
		$settings = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		foreach ($settings as $setting) {
			$cutoffDate = date('Y-m-d H:i:s', strtotime("-{$setting['retention_days']} days"));
			
			// Find old backups
			$stmt = $pdo->prepare('SELECT * FROM backup_logs WHERE backup_type = ? AND created_at < ?');
			$stmt->execute([$setting['backup_type'], $cutoffDate]);
			$oldBackups = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			foreach ($oldBackups as $backup) {
				// Delete file if it exists
				if (file_exists($backup['file_path'])) {
					unlink($backup['file_path']);
				}
				
				// Delete from database
				$stmt = $pdo->prepare('DELETE FROM backup_logs WHERE id = ?');
				$stmt->execute([$backup['id']]);
			}
		}
	}
	
	private function getDatabaseSchema(): string {
		$pdo = DB::pdo();
		$schema = "-- Database Schema Export\n";
		$schema .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
		
		// Get all table creation statements
		$stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
		$tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
		
		foreach ($tables as $tableSql) {
			$schema .= $tableSql . ";\n\n";
		}
		
		// Get all indexes
		$stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='index' AND name NOT LIKE 'sqlite_%' ORDER BY name");
		$indexes = $stmt->fetchAll(\PDO::FETCH_COLUMN);
		
		foreach ($indexes as $indexSql) {
			if ($indexSql) {
				$schema .= $indexSql . ";\n\n";
			}
		}
		
		return $schema;
	}
	
	private function getBackupDirectory(): string {
		// Try multiple possible backup locations
		$possiblePaths = [
			'/var/www/html/backups',  // Web server accessible
			'/tmp/event_manager_backups',  // Temporary directory
			__DIR__ . '/../backups',  // Relative to app directory
			sys_get_temp_dir() . '/event_manager_backups'  // System temp directory
		];
		
		foreach ($possiblePaths as $path) {
			if (is_dir($path) && is_writable($path)) {
				return $path;
			}
			
			// Try to create the directory
			if (!is_dir($path)) {
				if (@mkdir($path, 0755, true)) {
					return $path;
				}
			}
		}
		
		// If all else fails, use a subdirectory of the current directory
		$fallbackPath = __DIR__ . '/../backups';
		if (!is_dir($fallbackPath)) {
			@mkdir($fallbackPath, 0755, true);
		}
		
		return $fallbackPath;
	}
	
	private function getDatabasePath(): string {
		// Try multiple possible database locations
		$possiblePaths = [
			__DIR__ . '/../db/database.db',
			__DIR__ . '/../db/contest.sqlite',
			'/var/www/html/app/db/database.db',
			'/var/www/html/app/db/contest.sqlite',
			__DIR__ . '/../../app/db/database.db',
			__DIR__ . '/../../app/db/contest.sqlite',
			'/var/www/html/db/database.db',
			'/var/www/html/db/contest.sqlite',
			__DIR__ . '/../database.db',
			__DIR__ . '/../contest.sqlite',
			'/var/www/html/database.db',
			'/var/www/html/contest.sqlite'
		];
		
		foreach ($possiblePaths as $path) {
			if (file_exists($path) && is_readable($path)) {
				return $path;
			}
		}
		
		// If no database found, try to get the path from the DB class
		try {
			$pdo = DB::pdo();
			$dsn = $pdo->getAttribute(\PDO::ATTR_CONNECTION_STATUS);
			// Extract path from DSN if possible
			$dbPath = DB::getDatabasePath();
			if ($dbPath && file_exists($dbPath)) {
				return $dbPath;
			}
		} catch (\Exception $e) {
			// Continue to throw the original error
		}
		
		throw new \Exception('Database file not found or not readable. Checked paths: ' . implode(', ', $possiblePaths));
	}
	
	public function debugDatabasePath(): void {
		require_organizer();
		
		$debugInfo = [
			'current_dir' => __DIR__,
			'db_class_path' => DB::getDatabasePath(),
			'possible_paths' => [
				__DIR__ . '/../db/database.db',
				__DIR__ . '/../db/contest.sqlite',
				'/var/www/html/app/db/database.db',
				'/var/www/html/app/db/contest.sqlite',
				__DIR__ . '/../../app/db/database.db',
				__DIR__ . '/../../app/db/contest.sqlite',
				'/var/www/html/db/database.db',
				'/var/www/html/db/contest.sqlite',
				__DIR__ . '/../database.db',
				__DIR__ . '/../contest.sqlite',
				'/var/www/html/database.db',
				'/var/www/html/contest.sqlite'
			]
		];
		
		foreach ($debugInfo['possible_paths'] as $path) {
			$debugInfo['path_checks'][$path] = [
				'exists' => file_exists($path),
				'readable' => is_readable($path),
				'size' => file_exists($path) ? filesize($path) : 'N/A'
			];
		}
		
		echo '<pre>' . print_r($debugInfo, true) . '</pre>';
		exit;
	}
	
	public function debugScheduledBackups(): void {
		require_organizer();
		
		try {
			$pdo = DB::pdo();
			$now = date('Y-m-d H:i:s');
			
			echo '<pre>=== Scheduled Backup Debug Information ===</pre>';
			echo '<pre>Current time: ' . $now . '</pre>';
			echo '<pre>Current timestamp: ' . strtotime($now) . '</pre>';
			
			// Get all backup settings (enabled and disabled)
			$stmt = $pdo->query('SELECT * FROM backup_settings ORDER BY backup_type');
			$settings = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			echo '<pre>Found ' . count($settings) . ' backup settings:</pre>';
			
			foreach ($settings as $setting) {
				$nextRun = $setting['next_run'];
				$nextRunTimestamp = $nextRun ? strtotime($nextRun) : null;
				$shouldRun = false;
				
				if (empty($nextRun)) {
					$shouldRun = true;
				} else {
					$shouldRun = strtotime($now) >= $nextRunTimestamp;
				}
				
				echo '<pre>';
				echo "Backup Type: {$setting['backup_type']}\n";
				echo "Enabled: " . ($setting['enabled'] ? 'YES' : 'NO') . "\n";
				echo "Frequency: {$setting['frequency']} (every {$setting['frequency_value']})\n";
				echo "Retention: {$setting['retention_days']} days\n";
				echo "Last Run: " . ($setting['last_run'] ?: 'Never') . "\n";
				echo "Next Run: " . ($nextRun ?: 'Not set') . "\n";
				echo "Should Run: " . ($shouldRun ? 'YES' : 'NO') . "\n";
				echo "Calculated Next Run: " . $this->calculateNextRun($setting['frequency'], $setting['frequency_value'] ?? 1) . "\n";
				echo '</pre>';
			}
			
			// Test if we can run scheduled backups
			echo '<pre>=== Testing Scheduled Backup Execution ===</pre>';
			$enabledSettings = array_filter($settings, function($s) { return $s['enabled']; });
			echo '<pre>Enabled settings: ' . count($enabledSettings) . '</pre>';
			
			if (empty($enabledSettings)) {
				echo '<pre>⚠️ No enabled backup settings found!</pre>';
				echo '<pre>To enable backups, go to the backup settings and check the "Enable" checkbox.</pre>';
			} else {
				echo '<pre>✓ Found enabled backup settings</pre>';
			}
			
			exit;
			
		} catch (\Exception $e) {
			echo '<pre>Error: ' . $e->getMessage() . '</pre>';
			exit;
		}
	}
	
	public function forceLogoutAll(): void {
		require_organizer();
		
		try {
			$pdo = DB::pdo();
			
			// Test inserting different frequency values
			$testFrequencies = ['minutes', 'hours', 'daily', 'weekly', 'monthly'];
			$results = [];
			
			foreach ($testFrequencies as $frequency) {
				try {
					$testId = uuid();
					$stmt = $pdo->prepare('INSERT INTO backup_settings (id, backup_type, enabled, frequency, frequency_value, retention_days) VALUES (?, ?, ?, ?, ?, ?)');
					$stmt->execute([$testId, 'schema', 0, $frequency, 1, 30]);
					
					// Clean up test record
					$pdo->prepare('DELETE FROM backup_settings WHERE id = ?')->execute([$testId]);
					
					$results[$frequency] = 'SUCCESS';
				} catch (\PDOException $e) {
					$results[$frequency] = 'FAILED: ' . $e->getMessage();
				}
			}
			
			echo '<pre>Database Constraint Test Results:' . "\n";
			echo '=====================================' . "\n";
			foreach ($results as $frequency => $result) {
				echo sprintf('%-10s: %s' . "\n", $frequency, $result);
			}
			echo '</pre>';
			exit;
			
		} catch (\Exception $e) {
			echo '<pre>Error: ' . $e->getMessage() . '</pre>';
			exit;
		}
	}
	
	public function debugFormSubmission(): void {
		require_organizer();
		
		echo '<pre>Form Debug Information:' . "\n";
		echo '========================' . "\n";
		echo 'Request Method: ' . $_SERVER['REQUEST_METHOD'] . "\n";
		echo 'Content Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'Not set') . "\n";
		echo 'POST Data:' . "\n";
		print_r($_POST);
		echo 'GET Data:' . "\n";
		print_r($_GET);
		echo '</pre>';
		exit;
	}
	
	public function forceConstraintUpdate(): void {
		require_organizer();
		
		try {
			// Create a completely new database connection to avoid any existing transactions
			$dbPath = DB::getDatabasePath();
			$pdo = new \PDO('sqlite:' . $dbPath);
			$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			
			// Set WAL mode on the new connection
			$pdo->exec('PRAGMA journal_mode=WAL');
			$pdo->exec('PRAGMA busy_timeout=30000');
			
			// Force the constraint update
			$pdo->beginTransaction();
			
			// Create new table with updated constraint
			$pdo->exec('CREATE TABLE backup_settings_new (
				id TEXT PRIMARY KEY,
				backup_type TEXT NOT NULL CHECK (backup_type IN (\'schema\', \'full\')),
				enabled BOOLEAN NOT NULL DEFAULT 0,
				frequency TEXT NOT NULL CHECK (frequency IN (\'minutes\', \'hours\', \'daily\', \'weekly\', \'monthly\')),
				frequency_value INTEGER NOT NULL DEFAULT 1,
				retention_days INTEGER NOT NULL DEFAULT 30,
				last_run TEXT,
				next_run TEXT,
				created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
			)');
			
			// Copy data from old table
			$pdo->exec('INSERT INTO backup_settings_new SELECT * FROM backup_settings');
			
			// Drop old table and rename new one
			$pdo->exec('DROP TABLE backup_settings');
			$pdo->exec('ALTER TABLE backup_settings_new RENAME TO backup_settings');
			
			$pdo->commit();
			
			echo '<pre>Constraint update completed successfully!</pre>';
			exit;
			
		} catch (\Exception $e) {
			if (isset($pdo) && $pdo->inTransaction()) {
				$pdo->rollBack();
			}
			echo '<pre>Error: ' . $e->getMessage() . '</pre>';
			exit;
		}
	}
	
	public function forceConstraintUpdateSimple(): void {
		require_organizer();
		
		$maxRetries = 10;
		$retryDelay = 2; // seconds
		
		for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
			try {
				echo "<pre>Attempt $attempt of $maxRetries...</pre>";
				
				// Create a completely new database connection to avoid any existing transactions
				$dbPath = DB::getDatabasePath();
				$pdo = new \PDO('sqlite:' . $dbPath);
				$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
				
				// Set aggressive timeout settings
				$pdo->exec('PRAGMA journal_mode=DELETE');
				$pdo->exec('PRAGMA busy_timeout=60000'); // 60 seconds
				$pdo->exec('PRAGMA synchronous=NORMAL');
				$pdo->exec('PRAGMA cache_size=10000');
				$pdo->exec('PRAGMA temp_store=MEMORY');
				
				// Start a transaction
				$pdo->beginTransaction();
				
				// Create new table with updated constraint
				$pdo->exec('CREATE TABLE backup_settings_new (
					id TEXT PRIMARY KEY,
					backup_type TEXT NOT NULL CHECK (backup_type IN (\'schema\', \'full\')),
					enabled BOOLEAN NOT NULL DEFAULT 0,
					frequency TEXT NOT NULL CHECK (frequency IN (\'minutes\', \'hours\', \'daily\', \'weekly\', \'monthly\')),
					frequency_value INTEGER NOT NULL DEFAULT 1,
					retention_days INTEGER NOT NULL DEFAULT 30,
					last_run TEXT,
					next_run TEXT,
					created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
					updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
				)');
				
				// Copy data from old table
				$pdo->exec('INSERT INTO backup_settings_new SELECT * FROM backup_settings');
				
				// Drop old table and rename new one
				$pdo->exec('DROP TABLE backup_settings');
				$pdo->exec('ALTER TABLE backup_settings_new RENAME TO backup_settings');
				
				$pdo->commit();
				
				echo '<pre>Constraint update completed successfully!</pre>';
				exit;
				
			} catch (\Exception $e) {
				if (isset($pdo) && $pdo->inTransaction()) {
					$pdo->rollBack();
				}
				
				echo "<pre>Attempt $attempt failed: " . $e->getMessage() . "</pre>";
				
				if ($attempt < $maxRetries) {
					echo "<pre>Waiting $retryDelay seconds before retry...</pre>";
					sleep($retryDelay);
					$retryDelay *= 1.5; // Exponential backoff
				} else {
					echo '<pre>All attempts failed. Database may be heavily locked.</pre>';
					echo '<pre>Try closing all browser tabs and waiting a few minutes before trying again.</pre>';
					exit;
				}
			}
		}
	}
	
	public function runCliConstraintFix(): void {
		require_organizer();
		
		echo '<pre>Running command-line constraint fix...</pre>';
		echo '<pre>This may take a few moments...</pre>';
		
		// Flush output to show progress
		if (ob_get_level()) {
			ob_flush();
		}
		flush();
		
		$scriptPath = __DIR__ . '/../../fix_constraint_cli.php';
		
		if (!file_exists($scriptPath)) {
			echo '<pre>Error: CLI script not found at: ' . $scriptPath . '</pre>';
			exit;
		}
		
		// Run the CLI script
		$output = [];
		$returnCode = 0;
		
		exec("php \"$scriptPath\" 2>&1", $output, $returnCode);
		
		echo '<pre>CLI Script Output:</pre>';
		echo '<pre>' . implode("\n", $output) . '</pre>';
		
		if ($returnCode === 0) {
			echo '<pre>Constraint fix completed successfully!</pre>';
		} else {
			echo '<pre>Constraint fix failed with return code: ' . $returnCode . '</pre>';
		}
		
		exit;
	}
	
	public function runSqlite3ConstraintFix(): void {
		require_organizer();
		
		echo '<pre>Running sqlite3 command-line constraint fix...</pre>';
		echo '<pre>This bypasses PHP PDO transaction issues...</pre>';
		
		// Flush output to show progress
		if (ob_get_level()) {
			ob_flush();
		}
		flush();
		
		$scriptPath = __DIR__ . '/../../fix_constraint_sqlite3.php';
		
		if (!file_exists($scriptPath)) {
			echo '<pre>Error: SQLite3 script not found at: ' . $scriptPath . '</pre>';
			exit;
		}
		
		// Run the SQLite3 script
		$output = [];
		$returnCode = 0;
		
		exec("php \"$scriptPath\" 2>&1", $output, $returnCode);
		
		echo '<pre>SQLite3 Script Output:</pre>';
		echo '<pre>' . implode("\n", $output) . '</pre>';
		
		if ($returnCode === 0) {
			echo '<pre>Constraint fix completed successfully!</pre>';
		} else {
			echo '<pre>Constraint fix failed with return code: ' . $returnCode . '</pre>';
		}
		
		exit;
	}
	
	public function runShellConstraintFix(): void {
		require_organizer();
		
		echo '<pre>Running shell script constraint fix...</pre>';
		echo '<pre>This will stop web server, fix constraint, and restart...</pre>';
		
		// Flush output to show progress
		if (ob_get_level()) {
			ob_flush();
		}
		flush();
		
		$scriptPath = __DIR__ . '/../../fix_constraint.sh';
		
		if (!file_exists($scriptPath)) {
			echo '<pre>Error: Shell script not found at: ' . $scriptPath . '</pre>';
			exit;
		}
		
		// Run the shell script
		$output = [];
		$returnCode = 0;
		
		exec("bash \"$scriptPath\" 2>&1", $output, $returnCode);
		
		echo '<pre>Shell Script Output:</pre>';
		echo '<pre>' . implode("\n", $output) . '</pre>';
		
		if ($returnCode === 0) {
			echo '<pre>Constraint fix completed successfully!</pre>';
		} else {
			echo '<pre>Constraint fix failed with return code: ' . $returnCode . '</pre>';
		}
		
		exit;
	}
	
	public function fixBackupTimestamps(): void {
		require_organizer();
		
		try {
			$pdo = DB::pdo();
			$now = date('Y-m-d H:i:s');
			
			echo '<pre>Fixing backup timestamp inconsistencies...</pre>';
			echo '<pre>Current time: ' . $now . '</pre>';
			
			// Get all backup settings
			$stmt = $pdo->query('SELECT * FROM backup_settings');
			$settings = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			$fixed = 0;
			
			foreach ($settings as $setting) {
				$needsFix = false;
				$lastRun = $setting['last_run'];
				$nextRun = $setting['next_run'];
				
				// Check for inconsistencies
				if (!empty($lastRun) && !empty($nextRun)) {
					$lastRunTimestamp = strtotime($lastRun);
					$nextRunTimestamp = strtotime($nextRun);
					
					// If last_run is in the future or next_run is in the past, fix it
					if ($lastRunTimestamp > strtotime($now) || $nextRunTimestamp < strtotime($now)) {
						$needsFix = true;
					}
				} elseif (empty($nextRun) && !empty($lastRun)) {
					// If we have last_run but no next_run, calculate next_run
					$needsFix = true;
				} elseif (empty($lastRun) && !empty($nextRun)) {
					// If we have next_run but no last_run, and next_run is in the past, fix it
					if (strtotime($nextRun) < strtotime($now)) {
						$needsFix = true;
					}
				}
				
				if ($needsFix) {
					// Calculate proper next_run from current time
					$newNextRun = $this->calculateNextRun($setting['frequency'], $setting['frequency_value'] ?? 1);
					
					// Update the setting
					$stmt = $pdo->prepare('UPDATE backup_settings SET last_run = ?, next_run = ? WHERE id = ?');
					$stmt->execute([$now, $newNextRun, $setting['id']]);
					
					echo '<pre>Fixed ' . $setting['backup_type'] . ' backup:</pre>';
					echo '<pre>  Old last_run: ' . ($lastRun ?: 'NULL') . '</pre>';
					echo '<pre>  Old next_run: ' . ($nextRun ?: 'NULL') . '</pre>';
					echo '<pre>  New last_run: ' . $now . '</pre>';
					echo '<pre>  New next_run: ' . $newNextRun . '</pre>';
					
					$fixed++;
				}
			}
			
			echo '<pre>Fixed ' . $fixed . ' backup settings.</pre>';
			echo '<pre>Timestamp fix completed successfully!</pre>';
			exit;
			
		} catch (\Exception $e) {
			echo '<pre>Error: ' . $e->getMessage() . '</pre>';
			exit;
		}
	}
	
	public function restoreBackupSettings(): void {
		require_organizer();
		
		try {
			$pdo = DB::pdo();
			
			echo '<pre>Restoring default backup settings...</pre>';
			
			// Check if backup settings exist
			$stmt = $pdo->query('SELECT COUNT(*) FROM backup_settings');
			$count = $stmt->fetchColumn();
			
			if ($count > 0) {
				echo '<pre>Backup settings already exist (' . $count . ' records).</pre>';
				echo '<pre>Current settings:</pre>';
				
				$stmt = $pdo->query('SELECT * FROM backup_settings ORDER BY backup_type');
				$settings = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				
				foreach ($settings as $setting) {
					echo '<pre>  ' . $setting['backup_type'] . ': ' . ($setting['enabled'] ? 'enabled' : 'disabled') . ', ' . $setting['frequency'] . ' (' . $setting['frequency_value'] . '), ' . $setting['retention_days'] . ' days retention</pre>';
				}
				
				// Check if we're missing schema or full backup settings
				$hasSchema = false;
				$hasFull = false;
				foreach ($settings as $setting) {
					if ($setting['backup_type'] === 'schema') $hasSchema = true;
					if ($setting['backup_type'] === 'full') $hasFull = true;
				}
				
				if (!$hasSchema) {
					echo '<pre>⚠️ Missing schema backup setting, creating it...</pre>';
					$stmt = $pdo->prepare('INSERT INTO backup_settings (id, backup_type, enabled, frequency, frequency_value, retention_days) VALUES (?, ?, ?, ?, ?, ?)');
					$stmt->execute([uuid(), 'schema', 0, 'daily', 1, 30]);
					echo '<pre>✓ Created schema backup setting</pre>';
				}
				
				if (!$hasFull) {
					echo '<pre>⚠️ Missing full backup setting, creating it...</pre>';
					$stmt = $pdo->prepare('INSERT INTO backup_settings (id, backup_type, enabled, frequency, frequency_value, retention_days) VALUES (?, ?, ?, ?, ?, ?)');
					$stmt->execute([uuid(), 'full', 0, 'weekly', 1, 30]);
					echo '<pre>✓ Created full backup setting</pre>';
				}
				
				if ($hasSchema && $hasFull) {
					echo '<pre>✓ Both schema and full backup settings exist</pre>';
				}
				
				echo '<pre>Backup settings check completed!</pre>';
				exit;
			}
			
			// Create default backup settings
			$stmt = $pdo->prepare('INSERT INTO backup_settings (id, backup_type, enabled, frequency, frequency_value, retention_days) VALUES (?, ?, ?, ?, ?, ?)');
			$stmt->execute([uuid(), 'schema', 0, 'daily', 1, 30]);
			$stmt->execute([uuid(), 'full', 0, 'weekly', 1, 30]);
			
			echo '<pre>Successfully restored default backup settings:</pre>';
			echo '<pre>  Schema backup: disabled, daily frequency, 30-day retention</pre>';
			echo '<pre>  Full backup: disabled, weekly frequency, 30-day retention</pre>';
			echo '<pre>Backup settings restored successfully!</pre>';
			exit;
			
		} catch (\Exception $e) {
			echo '<pre>Error: ' . $e->getMessage() . '</pre>';
			exit;
		}
	}
	
	public function resetSessionVersions(): void {
		require_organizer();
		
		try {
			$pdo = DB::pdo();
			
			echo '<pre>Resetting all user session versions...</pre>';
			
			// Get all users
			$stmt = $pdo->query("SELECT id, email, preferred_name, role, session_version FROM users");
			$users = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			echo '<pre>Found ' . count($users) . ' users</pre>';
			
			$resetCount = 0;
			
			foreach ($users as $user) {
				// Generate a new session version
				$newSessionVersion = uuid();
				
				// Update the user's session version
				$stmt = $pdo->prepare("UPDATE users SET session_version = ? WHERE id = ?");
				$stmt->execute([$newSessionVersion, $user['id']]);
				
				echo '<pre>Reset session version for: ' . ($user['email'] ?: $user['preferred_name']) . ' (' . $user['role'] . ')</pre>';
				
				$resetCount++;
			}
			
			echo '<pre>Reset session versions for ' . $resetCount . ' users</pre>';
			echo '<pre>All users should now be able to log in normally</pre>';
			echo '<pre>Session version reset completed successfully!</pre>';
			exit;
			
		} catch (\Exception $e) {
			echo '<pre>Error: ' . $e->getMessage() . '</pre>';
			exit;
		}
	}
	
	public function checkSystemTime(): void {
		require_organizer();
		
		echo '<pre>=== System Time Check ===</pre>';
		echo '<pre>PHP date(): ' . date('Y-m-d H:i:s') . '</pre>';
		echo '<pre>PHP time(): ' . time() . '</pre>';
		echo '<pre>PHP strtotime("now"): ' . strtotime('now') . '</pre>';
		echo '<pre>PHP date("c"): ' . date('c') . '</pre>';
		echo '<pre>PHP timezone: ' . date_default_timezone_get() . '</pre>';
		
		// Check if we can get system time
		if (function_exists('shell_exec')) {
			$systemTime = shell_exec('date');
			echo '<pre>System date command: ' . trim($systemTime) . '</pre>';
		}
		
		// Check database time
		try {
			$pdo = DB::pdo();
			$stmt = $pdo->query('SELECT datetime("now") as db_time, strftime("%s", "now") as db_timestamp');
			$result = $stmt->fetch(\PDO::FETCH_ASSOC);
			echo '<pre>Database datetime: ' . $result['db_time'] . '</pre>';
			echo '<pre>Database timestamp: ' . $result['db_timestamp'] . '</pre>';
		} catch (\Exception $e) {
			echo '<pre>Database time error: ' . $e->getMessage() . '</pre>';
		}
		
		echo '<pre>=== Time Check Complete ===</pre>';
		exit;
	}
	
	public function debugBackupSettings(): void {
		require_organizer();
		
		echo '<pre>=== Backup Settings Debug ===</pre>';
		
		try {
			$pdo = DB::pdo();
			
			// Check if table exists
			$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='backup_settings'");
			if ($stmt->fetch()) {
				echo '<pre>✓ backup_settings table exists</pre>';
				
				// Get table schema
				$stmt = $pdo->query("PRAGMA table_info(backup_settings)");
				$columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				echo '<pre>Table columns:</pre>';
				foreach ($columns as $column) {
					echo '<pre>  - ' . $column['name'] . ' (' . $column['type'] . ')</pre>';
				}
				
				// Get all settings
				$stmt = $pdo->query('SELECT * FROM backup_settings ORDER BY backup_type');
				$settings = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				
				echo '<pre>Found ' . count($settings) . ' backup settings:</pre>';
				foreach ($settings as $setting) {
					echo '<pre>';
					echo "ID: {$setting['id']}\n";
					echo "Type: {$setting['backup_type']}\n";
					echo "Enabled: " . ($setting['enabled'] ? 'YES' : 'NO') . "\n";
					echo "Frequency: {$setting['frequency']}\n";
					echo "Frequency Value: {$setting['frequency_value']}\n";
					echo "Retention: {$setting['retention_days']} days\n";
					echo "Last Run: " . ($setting['last_run'] ?: 'Never') . "\n";
					echo "Next Run: " . ($setting['next_run'] ?: 'Not set') . "\n";
					echo "Created: {$setting['created_at']}\n";
					echo "Updated: {$setting['updated_at']}\n";
					echo '</pre>';
				}
				
				// Check for constraint violations
				echo '<pre>=== Testing Constraint ===</pre>';
				try {
					$testId = uuid();
					$stmt = $pdo->prepare('INSERT INTO backup_settings (id, backup_type, enabled, frequency, frequency_value, retention_days) VALUES (?, ?, ?, ?, ?, ?)');
					$stmt->execute([$testId, 'schema', 0, 'minutes', 5, 30]);
					
					// Clean up test record
					$pdo->prepare('DELETE FROM backup_settings WHERE id = ?')->execute([$testId]);
					echo '<pre>✓ Constraint allows minutes frequency</pre>';
				} catch (\Exception $e) {
					echo '<pre>✗ Constraint error: ' . $e->getMessage() . '</pre>';
				}
				
			} else {
				echo '<pre>✗ backup_settings table does NOT exist</pre>';
			}
			
		} catch (\Exception $e) {
			echo '<pre>Error: ' . $e->getMessage() . '</pre>';
		}
		
		echo '<pre>=== Debug Complete ===</pre>';
		exit;
	}
	
	private function ensureBackupSettingsTable(): void {
		$pdo = DB::pdo();
		
		// Check if table exists
		$stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='backup_settings'");
		if (!$stmt->fetch()) {
			// Table doesn't exist, create it
			$pdo->exec('CREATE TABLE backup_settings (
				id TEXT PRIMARY KEY,
				backup_type TEXT NOT NULL CHECK (backup_type IN (\'schema\', \'full\')),
				enabled BOOLEAN NOT NULL DEFAULT 0,
				frequency TEXT NOT NULL CHECK (frequency IN (\'minutes\', \'hours\', \'daily\', \'weekly\', \'monthly\')),
				frequency_value INTEGER NOT NULL DEFAULT 1,
				retention_days INTEGER NOT NULL DEFAULT 30,
				last_run TEXT,
				next_run TEXT,
				created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
				updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
			)');
			
			\App\Logger::info('Created backup_settings table');
		}
		
		// Ensure frequency_value column exists
		$stmt = $pdo->query("PRAGMA table_info(backup_settings)");
		$columns = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		$hasFrequencyValue = false;
		
		foreach ($columns as $column) {
			if ($column['name'] === 'frequency_value') {
				$hasFrequencyValue = true;
				break;
			}
		}
		
		if (!$hasFrequencyValue) {
			$pdo->exec('ALTER TABLE backup_settings ADD COLUMN frequency_value INTEGER NOT NULL DEFAULT 1');
			\App\Logger::info('Added frequency_value column to backup_settings table');
		}
	}
}

class CategoryController {
	public function index(array $params): void {
		require_organizer();
		$contestId = param('id', $params);
		$contest = DB::pdo()->prepare('SELECT * FROM contests WHERE id = ?');
		$contest->execute([$contestId]);
		$contest = $contest->fetch(\PDO::FETCH_ASSOC);
		$categories = DB::pdo()->prepare('SELECT * FROM categories WHERE contest_id = ?');
		$categories->execute([$contestId]);
		$categories = $categories->fetchAll(\PDO::FETCH_ASSOC);
		view('categories/index', compact('contest','categories'));
	}
	public function new(array $params): void {
		require_organizer();
		$contestId = param('id', $params);
		$contest = DB::pdo()->prepare('SELECT * FROM contests WHERE id = ?');
		$contest->execute([$contestId]);
		$contest = $contest->fetch(\PDO::FETCH_ASSOC);
		view('categories/new', compact('contest'));
	}
	public function create(array $params): void {
		require_organizer();
		$contestId = param('id', $params);
		$name = post('name');
		
		// Debug log category creation attempt
		\App\Logger::debug('category_creation_attempt', 'category', null, 
			"Attempting to create category: contest_id={$contestId}, name={$name}");
		
		try {
			$categoryId = uuid();
			$stmt = DB::pdo()->prepare('INSERT INTO categories (id, contest_id, name) VALUES (?, ?, ?)');
			$stmt->execute([$categoryId, $contestId, $name]);
			
			// Log successful outcome
			\App\Logger::debug('category_creation_success', 'category', $categoryId, 
				"Category created successfully: category_id={$categoryId}, contest_id={$contestId}, name={$name}");
			\App\Logger::logAdminAction('category_created', 'category', $categoryId, 
				"Category created: {$name} in contest {$contestId}");
			
			redirect('/contests/' . $contestId . '/categories');
		} catch (\Exception $e) {
			// Log failure outcome
			\App\Logger::debug('category_creation_failed', 'category', null, 
				"Category creation failed: " . $e->getMessage());
			\App\Logger::error('category_creation_failed', 'category', null, 
				"Category creation failed: " . $e->getMessage());
			
			redirect('/contests/' . $contestId . '/categories?error=creation_failed');
		}
	}
}

class ContestSubcategoryController {
	public function index(array $params): void {
		require_organizer();
		$contestId = param('id', $params);
		$contest = DB::pdo()->prepare('SELECT * FROM contests WHERE id = ?');
		$contest->execute([$contestId]);
		$contest = $contest->fetch(\PDO::FETCH_ASSOC);
		
		// Get all subcategories for this contest
		$sql = 'SELECT sc.*, c.name as category_name 
				FROM subcategories sc 
				JOIN categories c ON sc.category_id = c.id 
				WHERE c.contest_id = ? 
				ORDER BY c.name, sc.name';
		$stmt = DB::pdo()->prepare($sql);
		$stmt->execute([$contestId]);
		$subcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		view('contests/subcategories', compact('contest','subcategories'));
	}
}

class SubcategoryController {
	public function index(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$category = DB::pdo()->prepare('SELECT * FROM categories WHERE id = ?');
		$category->execute([$categoryId]);
		$category = $category->fetch(\PDO::FETCH_ASSOC);
		$subcategories = DB::pdo()->prepare('SELECT * FROM subcategories WHERE category_id = ?');
		$subcategories->execute([$categoryId]);
		$subcategories = $subcategories->fetchAll(\PDO::FETCH_ASSOC);
		view('subcategories/index', compact('category','subcategories'));
	}
	public function new(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$category = DB::pdo()->prepare('SELECT * FROM categories WHERE id = ?');
		$category->execute([$categoryId]);
		$category = $category->fetch(\PDO::FETCH_ASSOC);
		view('subcategories/new', compact('category'));
	}
	public function create(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$name = post('name');
		$description = post('description') ?: null;
		$scoreCap = post('score_cap') ?: null;
		
		// Debug log subcategory creation attempt
		\App\Logger::debug('subcategory_creation_attempt', 'subcategory', null, 
			"Attempting to create subcategory: category_id={$categoryId}, name={$name}, score_cap={$scoreCap}");
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			$subcategoryId = uuid();
			$stmt = $pdo->prepare('INSERT INTO subcategories (id, category_id, name, description, score_cap) VALUES (?, ?, ?, ?, ?)');
			$stmt->execute([$subcategoryId, $categoryId, $name, $description, $scoreCap]);
			\App\Logger::debug('subcategory_created', 'subcategory', $subcategoryId, 
				"Subcategory created: subcategory_id={$subcategoryId}, category_id={$categoryId}, name={$name}");
		
		// Automatically assign category-level contestants and judges to this new subcategory
		$categoryContestants = $pdo->prepare('SELECT contestant_id FROM category_contestants WHERE category_id = ?');
		$categoryContestants->execute([$categoryId]);
		$contestantIds = array_column($categoryContestants->fetchAll(\PDO::FETCH_ASSOC), 'contestant_id');
		
		$categoryJudges = $pdo->prepare('SELECT judge_id FROM category_judges WHERE category_id = ?');
		$categoryJudges->execute([$categoryId]);
		$judgeIds = array_column($categoryJudges->fetchAll(\PDO::FETCH_ASSOC), 'judge_id');
		
		$insC = $pdo->prepare('INSERT INTO subcategory_contestants (subcategory_id, contestant_id) VALUES (?, ?)');
		$insJ = $pdo->prepare('INSERT INTO subcategory_judges (subcategory_id, judge_id) VALUES (?, ?)');
		
		foreach ($contestantIds as $id) {
			$insC->execute([$subcategoryId, $id]);
		}
		foreach ($judgeIds as $id) {
			$insJ->execute([$subcategoryId, $id]);
		}
		\App\Logger::debug('subcategory_assignments', 'subcategory', $subcategoryId, 
			"Subcategory assignments: contestants=" . count($contestantIds) . ", judges=" . count($judgeIds));
		
		// Create a default criterion with max score 60 if none exist
		$insC = $pdo->prepare('INSERT INTO criteria (id, subcategory_id, name, max_score) VALUES (?, ?, ?, ?)');
		$insC->execute([uuid(), $subcategoryId, 'Overall Performance', 60]);
		\App\Logger::debug('subcategory_default_criterion', 'subcategory', $subcategoryId, 
			"Default criterion created: 'Overall Performance' with max_score=60");
		
		$pdo->commit();
		
		// Log successful outcome
		\App\Logger::debug('subcategory_creation_success', 'subcategory', $subcategoryId, 
			"Subcategory creation completed successfully: subcategory_id={$subcategoryId}, category_id={$categoryId}, name={$name}");
		\App\Logger::logAdminAction('subcategory_created', 'subcategory', $subcategoryId, 
			"Subcategory created: {$name} in category {$categoryId}");
		
		redirect('/categories/' . $categoryId . '/subcategories');
	} catch (\Exception $e) {
		$pdo->rollBack();
		
		// Log failure outcome
		\App\Logger::debug('subcategory_creation_failed', 'subcategory', null, 
			"Subcategory creation failed: " . $e->getMessage());
		\App\Logger::error('subcategory_creation_failed', 'subcategory', null, 
			"Subcategory creation failed: " . $e->getMessage());
		
		redirect('/categories/' . $categoryId . '/subcategories?error=creation_failed');
	}
	}
	
	public function templates(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$category = DB::pdo()->prepare('SELECT * FROM categories WHERE id = ?');
		$category->execute([$categoryId]);
		$category = $category->fetch(\PDO::FETCH_ASSOC);
		$templates = DB::pdo()->query('SELECT * FROM subcategory_templates ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		view('subcategories/templates', compact('category','templates'));
	}
	public function createFromTemplate(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$templateId = post('template_id');
		$scoreCap = post('score_cap') ?: null;
		
		// Get template info
		$template = DB::pdo()->prepare('SELECT * FROM subcategory_templates WHERE id = ?');
		$template->execute([$templateId]);
		$template = $template->fetch(\PDO::FETCH_ASSOC);
		
		if (!$template) {
			redirect('/categories/' . $categoryId . '/subcategories');
			return;
		}
		
		// Get subcategory names from template
		$subcategoryNames = [];
		if (!empty($template['subcategory_names'])) {
			$subcategoryNames = json_decode($template['subcategory_names'], true) ?: [];
		}
		
		if (empty($subcategoryNames)) {
			redirect('/categories/' . $categoryId . '/subcategories');
			return;
		}
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		// Create each subcategory
		foreach ($subcategoryNames as $subcategoryName) {
			$subcategoryId = uuid();
			$stmt = $pdo->prepare('INSERT INTO subcategories (id, category_id, name, description, score_cap) VALUES (?, ?, ?, ?, ?)');
			$stmt->execute([$subcategoryId, $categoryId, $subcategoryName, $template['description'], $scoreCap]);
			
			// Copy criteria from template
			$templateCriteria = $pdo->prepare('SELECT * FROM template_criteria WHERE template_id = ?');
			$templateCriteria->execute([$templateId]);
			$criteria = $templateCriteria->fetchAll(\PDO::FETCH_ASSOC);
			$insC = $pdo->prepare('INSERT INTO criteria (id, subcategory_id, name, max_score) VALUES (?, ?, ?, ?)');
			foreach ($criteria as $c) {
				$insC->execute([uuid(), $subcategoryId, $c['name'], $c['max_score']]);
			}
			
			// If no criteria exist in template, create a default criterion with template's max score
			if (empty($criteria)) {
				$templateMaxScore = $template['max_score'] ?? 60;
				$insC->execute([uuid(), $subcategoryId, 'Overall Performance', $templateMaxScore]);
			}
			
			// Automatically assign category-level contestants and judges to this new subcategory
			$categoryContestants = $pdo->prepare('SELECT contestant_id FROM category_contestants WHERE category_id = ?');
			$categoryContestants->execute([$categoryId]);
			$contestantIds = array_column($categoryContestants->fetchAll(\PDO::FETCH_ASSOC), 'contestant_id');
			
			$categoryJudges = $pdo->prepare('SELECT judge_id FROM category_judges WHERE category_id = ?');
			$categoryJudges->execute([$categoryId]);
			$judgeIds = array_column($categoryJudges->fetchAll(\PDO::FETCH_ASSOC), 'judge_id');
			
			$insSC = $pdo->prepare('INSERT INTO subcategory_contestants (subcategory_id, contestant_id) VALUES (?, ?)');
			$insSJ = $pdo->prepare('INSERT INTO subcategory_judges (subcategory_id, judge_id) VALUES (?, ?)');
			
			foreach ($contestantIds as $id) {
				$insSC->execute([$subcategoryId, $id]);
			}
			foreach ($judgeIds as $id) {
				$insSJ->execute([$subcategoryId, $id]);
			}
		}
		
		$pdo->commit();
		$_SESSION['success_message'] = 'Created ' . count($subcategoryNames) . ' subcategories from template successfully!';
		redirect('/categories/' . $categoryId . '/subcategories');
	}
	
	public function bulkDelete(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$subcategoryIds = post('subcategory_ids', []);
		
		if (empty($subcategoryIds)) {
			redirect('/categories/' . $categoryId . '/subcategories?error=no_subcategories_selected');
			return;
		}
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			foreach ($subcategoryIds as $subcategoryId) {
				// Delete all related data
				$pdo->prepare('DELETE FROM judge_certifications WHERE subcategory_id = ?')->execute([$subcategoryId]);
				$pdo->prepare('DELETE FROM judge_comments WHERE subcategory_id = ?')->execute([$subcategoryId]);
				$pdo->prepare('DELETE FROM scores WHERE subcategory_id = ?')->execute([$subcategoryId]);
				$pdo->prepare('DELETE FROM criteria WHERE subcategory_id = ?')->execute([$subcategoryId]);
				$pdo->prepare('DELETE FROM subcategory_contestants WHERE subcategory_id = ?')->execute([$subcategoryId]);
				$pdo->prepare('DELETE FROM subcategory_judges WHERE subcategory_id = ?')->execute([$subcategoryId]);
				$pdo->prepare('DELETE FROM subcategories WHERE id = ?')->execute([$subcategoryId]);
			}
			$pdo->commit();
			redirect('/categories/' . $categoryId . '/subcategories?success=subcategories_deleted');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/categories/' . $categoryId . '/subcategories?error=delete_failed');
		}
	}
	
	public function bulkUpdate(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$updates = post('updates', []);
		
		if (empty($updates)) {
			redirect('/categories/' . $categoryId . '/subcategories?error=no_updates');
			return;
		}
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			foreach ($updates as $subcategoryId => $data) {
				if (isset($data['name']) || isset($data['description']) || isset($data['score_cap'])) {
					$fields = [];
					$values = [];
					
					if (isset($data['name'])) {
						$fields[] = 'name = ?';
						$values[] = $data['name'];
					}
					if (isset($data['description'])) {
						$fields[] = 'description = ?';
						$values[] = $data['description'];
					}
					if (isset($data['score_cap'])) {
						$fields[] = 'score_cap = ?';
						$values[] = (int)$data['score_cap'];
					}
					
					$values[] = $subcategoryId;
					$pdo->prepare('UPDATE subcategories SET ' . implode(', ', $fields) . ' WHERE id = ?')
						->execute($values);
				}
			}
			$pdo->commit();
			redirect('/categories/' . $categoryId . '/subcategories?success=subcategories_updated');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/categories/' . $categoryId . '/subcategories?error=update_failed');
		}
	}
}

class PeopleController {
	public function index(): void {
		require_organizer();
		
		// Debug log data retrieval
		\App\Logger::debug('people_index_data_retrieval', 'people', null, 
			"Retrieving all contestants and judges");
		
		// Get ALL contestants (with user info if available)
		$contestants = DB::pdo()->query('
			SELECT c.*, u.preferred_name, u.password_hash
			FROM contestants c 
			LEFT JOIN users u ON c.id = u.contestant_id 
			ORDER BY c.contestant_number IS NULL, c.contestant_number, c.name
		')->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get ALL judges (with user info if available)
		$judges = DB::pdo()->query('
			SELECT j.*, u.preferred_name, u.password_hash
			FROM judges j 
			LEFT JOIN users u ON j.id = u.judge_id 
			ORDER BY j.name
		')->fetchAll(\PDO::FETCH_ASSOC);
		
		\App\Logger::debug('people_index_data_retrieved', 'people', null, 
			"Retrieved " . count($contestants) . " contestants and " . count($judges) . " judges");
		
		view('people/index', compact('contestants','judges'));
	}
	public function new(): void { require_organizer(); view('people/new'); }
	public function createContestant(): void {
		require_organizer();
		$imagePath = null;
		
		// Handle image upload
		if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = __DIR__ . '/../../public/uploads/contestants/';
			// Create directory if it doesn't exist
			if (!is_dir($uploadDir)) {
				mkdir($uploadDir, 0755, true);
			}
			$extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
			$filename = uuid() . '.' . $extension;
			$imagePath = '/uploads/contestants/' . $filename;
			
			if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
				$_SESSION['error_message'] = 'Failed to upload image - check directory permissions';
				redirect('/people/new');
				return;
			}
		}
		
		// Auto-assign contestant number if not provided
		$contestantNumber = post('contestant_number');
		if (!$contestantNumber) {
			$stmt = DB::pdo()->query('SELECT MAX(contestant_number) as max_num FROM contestants WHERE contestant_number IS NOT NULL');
			$result = $stmt->fetch(\PDO::FETCH_ASSOC);
			$contestantNumber = ($result['max_num'] ?? 0) + 1;
		}
		
		$stmt = DB::pdo()->prepare('INSERT INTO contestants (id, name, email, contestant_number, bio, image_path) VALUES (?, ?, ?, ?, ?, ?)');
		$stmt->execute([uuid(), post('name'), post('email') ?: null, $contestantNumber, post('bio') ?: null, $imagePath]);
		redirect('/people');
	}
	public function createJudge(): void {
		require_organizer();
		$imagePath = null;
		
		// Handle image upload
		if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = __DIR__ . '/../../public/uploads/judges/';
			// Create directory if it doesn't exist
			if (!is_dir($uploadDir)) {
				mkdir($uploadDir, 0755, true);
			}
			$extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
			$filename = uuid() . '.' . $extension;
			$imagePath = '/uploads/judges/' . $filename;
			
			if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
				$_SESSION['error_message'] = 'Failed to upload image - check directory permissions';
				redirect('/people/new');
				return;
			}
		}
		
		$stmt = DB::pdo()->prepare('INSERT INTO judges (id, name, email, bio, image_path) VALUES (?, ?, ?, ?, ?)');
		$stmt->execute([uuid(), post('name'), post('email') ?: null, post('bio') ?: null, $imagePath]);
		redirect('/people');
	}
	public function editContestant(array $params): void {
		require_organizer();
		$id = param('id', $params);
		$stmt = DB::pdo()->prepare('SELECT * FROM contestants WHERE id = ?');
		$stmt->execute([$id]);
		$contestant = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$contestant) {
			redirect('/people');
			return;
		}
		view('people/edit_contestant', compact('contestant'));
	}
	public function updateContestant(array $params): void {
		require_organizer();
		$id = param('id', $params);
		$name = post('name');
		$email = post('email') ?: null;
		$contestantNumber = post('contestant_number') ?: null;
		$bio = post('bio') ?: null;
		
		// Debug log contestant update attempt
		\App\Logger::debug('contestant_update_attempt', 'contestant', $id, 
			"Attempting to update contestant: contestant_id={$id}, name={$name}, email={$email}");
		
		$imagePath = null;
		
		// Handle image upload
		if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = __DIR__ . '/../../public/uploads/contestants/';
			// Create directory if it doesn't exist
			if (!is_dir($uploadDir)) {
				mkdir($uploadDir, 0755, true);
			}
			$extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
			$filename = uuid() . '.' . $extension;
			$imagePath = '/uploads/contestants/' . $filename;
			
			if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
				$_SESSION['error_message'] = 'Failed to upload image - check directory permissions';
				redirect('/people/contestants/' . $id . '/edit');
				return;
			}
		}
		
		// Get current image path if no new image uploaded
		if (!$imagePath) {
			$stmt = DB::pdo()->prepare('SELECT image_path FROM contestants WHERE id = ?');
			$stmt->execute([$id]);
			$current = $stmt->fetch(\PDO::FETCH_ASSOC);
			$imagePath = $current['image_path'] ?? null;
		}
		
		$stmt = DB::pdo()->prepare('UPDATE contestants SET name = ?, email = ?, gender = ?, contestant_number = ?, bio = ?, image_path = ? WHERE id = ?');
		$stmt->execute([post('name'), post('email') ?: null, post('gender') ?: null, post('contestant_number') ?: null, post('bio') ?: null, $imagePath, $id]);
		
		// Log successful outcome
		\App\Logger::debug('contestant_update_success', 'contestant', $id, 
			"Contestant updated successfully: contestant_id={$id}, name={$name}, email={$email}");
		\App\Logger::logAdminAction('contestant_updated', 'contestant', $id, 
			"Contestant updated: {$name}");
		
		$_SESSION['success_message'] = 'Contestant updated successfully!';
		redirect('/people');
	}
	public function deleteContestant(array $params): void {
		require_organizer();
		$id = param('id', $params);
		
		// Debug log deletion attempt
		\App\Logger::debug('contestant_deletion_attempt', 'contestant', $id, 
			"Attempting to delete contestant: contestant_id={$id}");
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Get contestant info for logging
			$stmt = $pdo->prepare('SELECT * FROM contestants WHERE id = ?');
			$stmt->execute([$id]);
			$contestant = $stmt->fetch(\PDO::FETCH_ASSOC);
			
			if (!$contestant) {
				\App\Logger::debug('contestant_deletion_failed', 'contestant', $id, 
					"Contestant deletion failed: contestant not found");
				redirect('/people?error=contestant_not_found');
				return;
			}
			
			\App\Logger::debug('contestant_deletion_details', 'contestant', $id, 
				"Contestant deletion details: name={$contestant['name']}, email={$contestant['email']}");
			
			// Delete associated image file
			$stmt = $pdo->prepare('SELECT image_path FROM contestants WHERE id = ?');
			$stmt->execute([$id]);
			$imagePath = $stmt->fetchColumn();
			if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
				unlink(__DIR__ . '/../../public' . $imagePath);
				\App\Logger::debug('contestant_image_deleted', 'contestant', $id, 
					"Contestant image file deleted: {$imagePath}");
			}
			
			// Delete all related data
			$pdo->prepare('DELETE FROM judge_comments WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM scores WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM subcategory_contestants WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM category_contestants WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM contestants WHERE id = ?')->execute([$id]);
			
			$pdo->commit();
			
			// Log successful outcome
			\App\Logger::debug('contestant_deletion_success', 'contestant', $id, 
				"Contestant deletion completed successfully: contestant_id={$id}, name={$contestant['name']}");
			\App\Logger::logUserDeletion($id, $contestant['name'], 'contestant');
			
			$_SESSION['success_message'] = 'Contestant and all associated data deleted successfully!';
			redirect('/people');
		} catch (\Exception $e) {
			$pdo->rollBack();
			
			// Log failure outcome
			\App\Logger::debug('contestant_deletion_failed', 'contestant', $id, 
				"Contestant deletion failed: " . $e->getMessage());
			\App\Logger::error('contestant_deletion_failed', 'contestant', $id, 
				"Contestant deletion failed: " . $e->getMessage());
			
			$_SESSION['error_message'] = 'Failed to delete contestant: ' . $e->getMessage();
			redirect('/people');
		}
	}
	public function viewJudgeBio(array $params): void {
		require_login();
		$id = param('id', $params);
		$stmt = DB::pdo()->prepare('SELECT * FROM judges WHERE id = ?');
		$stmt->execute([$id]);
		$judge = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$judge) {
			redirect('/people');
			return;
		}
		view('people/judge_bio', compact('judge'));
	}
	public function editJudge(array $params): void {
		require_organizer();
		$id = param('id', $params);
		$stmt = DB::pdo()->prepare('SELECT * FROM judges WHERE id = ?');
		$stmt->execute([$id]);
		$judge = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$judge) {
			redirect('/people');
			return;
		}
		view('people/edit_judge', compact('judge'));
	}
	public function updateJudge(array $params): void {
		require_organizer();
		$id = param('id', $params);
		$name = post('name');
		$email = post('email') ?: null;
		$bio = post('bio') ?: null;
		
		// Debug log judge update attempt
		\App\Logger::debug('judge_update_attempt', 'judge', $id, 
			"Attempting to update judge: judge_id={$id}, name={$name}, email={$email}");
		
		$imagePath = null;
		
		// Handle image upload
		if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$uploadDir = __DIR__ . '/../../public/uploads/judges/';
			// Create directory if it doesn't exist
			if (!is_dir($uploadDir)) {
				mkdir($uploadDir, 0755, true);
			}
			$extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
			$filename = uuid() . '.' . $extension;
			$imagePath = '/uploads/judges/' . $filename;
			
			if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
				$_SESSION['error_message'] = 'Failed to upload image - check directory permissions';
				redirect('/people/judges/' . $id . '/edit');
				return;
			}
		}
		
		// Get current image path if no new image uploaded
		if (!$imagePath) {
			$stmt = DB::pdo()->prepare('SELECT image_path FROM judges WHERE id = ?');
			$stmt->execute([$id]);
			$current = $stmt->fetch(\PDO::FETCH_ASSOC);
			$imagePath = $current['image_path'] ?? null;
		}
		
		$stmt = DB::pdo()->prepare('UPDATE judges SET name = ?, email = ?, gender = ?, bio = ?, image_path = ? WHERE id = ?');
		$stmt->execute([post('name'), post('email') ?: null, post('gender') ?: null, post('bio') ?: null, $imagePath, $id]);
		
		// Log successful outcome
		\App\Logger::debug('judge_update_success', 'judge', $id, 
			"Judge updated successfully: judge_id={$id}, name={$name}, email={$email}");
		\App\Logger::logAdminAction('judge_updated', 'judge', $id, 
			"Judge updated: {$name}");
		
		$_SESSION['success_message'] = 'Judge updated successfully!';
		redirect('/people');
	}
	public function deleteJudge(array $params): void {
		require_organizer();
		$id = param('id', $params);
		
		// Debug log deletion attempt
		\App\Logger::debug('judge_deletion_attempt', 'judge', $id, 
			"Attempting to delete judge: judge_id={$id}");
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Get judge info for logging
			$stmt = $pdo->prepare('SELECT * FROM judges WHERE id = ?');
			$stmt->execute([$id]);
			$judge = $stmt->fetch(\PDO::FETCH_ASSOC);
			
			if (!$judge) {
				\App\Logger::debug('judge_deletion_failed', 'judge', $id, 
					"Judge deletion failed: judge not found");
				redirect('/people?error=judge_not_found');
				return;
			}
			
			\App\Logger::debug('judge_deletion_details', 'judge', $id, 
				"Judge deletion details: name={$judge['name']}, email={$judge['email']}");
			
			// Delete associated image file
			$stmt = $pdo->prepare('SELECT image_path FROM judges WHERE id = ?');
			$stmt->execute([$id]);
			$imagePath = $stmt->fetchColumn();
			if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
				unlink(__DIR__ . '/../../public' . $imagePath);
				\App\Logger::debug('judge_image_deleted', 'judge', $id, 
					"Judge image file deleted: {$imagePath}");
			}
			
			// Delete all related data
			$pdo->prepare('DELETE FROM judge_certifications WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM judge_comments WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM scores WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM subcategory_judges WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM category_judges WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM judges WHERE id = ?')->execute([$id]);
			
			$pdo->commit();
			
			// Log successful outcome
			\App\Logger::debug('judge_deletion_success', 'judge', $id, 
				"Judge deletion completed successfully: judge_id={$id}, name={$judge['name']}");
			\App\Logger::logUserDeletion($id, $judge['name'], 'judge');
			
			$_SESSION['success_message'] = 'Judge and all associated data deleted successfully!';
			redirect('/people');
		} catch (\Exception $e) {
			$pdo->rollBack();
			
			// Log failure outcome
			\App\Logger::debug('judge_deletion_failed', 'judge', $id, 
				"Judge deletion failed: " . $e->getMessage());
			\App\Logger::error('judge_deletion_failed', 'judge', $id, 
				"Judge deletion failed: " . $e->getMessage());
			
			$_SESSION['error_message'] = 'Failed to delete judge: ' . $e->getMessage();
			redirect('/people');
		}
	}
}

class AssignmentController {
	public function edit(array $params): void {
		$subcategoryId = param('id', $params);
		if (is_judge()) {
			// ensure judge is assigned to this subcategory
			$allowed = DB::pdo()->prepare('SELECT 1 FROM subcategory_judges WHERE subcategory_id = ? AND judge_id = ?');
			$allowed->execute([$subcategoryId, current_user()['judge_id'] ?? '']);
			if (!$allowed->fetchColumn()) { http_response_code(403); echo 'Forbidden'; return; }
		} else {
			require_organizer();
		}
		$subcategory = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.id = ?');
		$subcategory->execute([$subcategoryId]);
		$subcategory = $subcategory->fetch(\PDO::FETCH_ASSOC);
		$contestants = DB::pdo()->query('SELECT * FROM contestants ORDER BY contestant_number IS NULL, contestant_number, name')->fetchAll(\PDO::FETCH_ASSOC);
		$judges = DB::pdo()->query('SELECT * FROM judges ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		$assignedContestants = DB::pdo()->prepare('SELECT contestant_id FROM subcategory_contestants WHERE subcategory_id = ?');
		$assignedContestants->execute([$subcategoryId]);
		$assignedContestants = array_column($assignedContestants->fetchAll(\PDO::FETCH_ASSOC), 'contestant_id');
		$assignedJudges = DB::pdo()->prepare('SELECT judge_id FROM subcategory_judges WHERE subcategory_id = ?');
		$assignedJudges->execute([$subcategoryId]);
		$assignedJudges = array_column($assignedJudges->fetchAll(\PDO::FETCH_ASSOC), 'judge_id');
		view('assignments/edit', compact('subcategory','contestants','judges','assignedContestants','assignedJudges'));
	}
	public function update(array $params): void {
		require_organizer();
		$subcategoryId = param('id', $params);
		$contestants = request_array('contestants');
		$judges = request_array('judges');
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		$pdo->prepare('DELETE FROM subcategory_contestants WHERE subcategory_id = ?')->execute([$subcategoryId]);
		$pdo->prepare('DELETE FROM subcategory_judges WHERE subcategory_id = ?')->execute([$subcategoryId]);
		$insC = $pdo->prepare('INSERT INTO subcategory_contestants (subcategory_id, contestant_id) VALUES (?, ?)');
		$insJ = $pdo->prepare('INSERT INTO subcategory_judges (subcategory_id, judge_id) VALUES (?, ?)');
		foreach ($contestants as $id) { if ($id) $insC->execute([$subcategoryId, $id]); }
		foreach ($judges as $id) { if ($id) $insJ->execute([$subcategoryId, $id]); }
		$pdo->commit();
		$_SESSION['success_message'] = 'Assignments updated successfully!';
		redirect('/subcategories/' . $subcategoryId . '/assign');
	}
}
class CriteriaController {
	public function index(array $params): void {
		require_organizer(); // Only organizers can manage criteria
		$subcategoryId = param('id', $params);
		$subcategory = DB::pdo()->prepare('SELECT * FROM subcategories WHERE id = ?');
		$subcategory->execute([$subcategoryId]);
		$subcategory = $subcategory->fetch(\PDO::FETCH_ASSOC);
		$stmt = DB::pdo()->prepare('SELECT * FROM criteria WHERE subcategory_id = ?');
		$stmt->execute([$subcategoryId]);
		$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		view('criteria/index', compact('subcategory','rows'));
	}
	public function new(array $params): void {
		require_organizer();
		$subcategoryId = param('id', $params);
		$subcategory = DB::pdo()->prepare('SELECT * FROM subcategories WHERE id = ?');
		$subcategory->execute([$subcategoryId]);
		$subcategory = $subcategory->fetch(\PDO::FETCH_ASSOC);
		view('criteria/new', compact('subcategory'));
	}
	public function create(array $params): void {
		require_organizer();
		$subcategoryId = param('id', $params);
		$maxScore = post('max_score') ?: 60; // Default to 60 if not provided
		
		// Debug log criteria creation attempt
		\App\Logger::debug('criteria_creation_attempt', 'criteria', null, 
			"Attempting to create criteria: subcategory_id={$subcategoryId}, max_score={$maxScore}");
		
		// Auto-generate criterion name
		$stmt = DB::pdo()->prepare('SELECT COUNT(*) as count FROM criteria WHERE subcategory_id = ?');
		$stmt->execute([$subcategoryId]);
		$count = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
		$criterionName = 'Criterion ' . ($count + 1);
		
		$stmt = DB::pdo()->prepare('INSERT INTO criteria (id, subcategory_id, name, max_score) VALUES (?, ?, ?, ?)');
		$criterionId = uuid();
		$stmt->execute([$criterionId, $subcategoryId, $criterionName, (int)$maxScore]);
		
		// Log successful outcome
		\App\Logger::debug('criteria_creation_success', 'criteria', $criterionId, 
			"Criteria created successfully: criterion_id={$criterionId}, subcategory_id={$subcategoryId}, name={$criterionName}, max_score={$maxScore}");
		\App\Logger::logAdminAction('criteria_created', 'criteria', $criterionId, 
			"Criteria created: {$criterionName} (max: {$maxScore})");
		
		redirect('/subcategories/' . $subcategoryId . '/criteria');
	}
	
	public function bulkDelete(array $params): void {
		require_organizer();
		$subcategoryId = param('id', $params);
		$criteriaIds = post('criteria_ids', []);
		
		if (empty($criteriaIds)) {
			redirect('/subcategories/' . $subcategoryId . '/criteria?error=no_criteria_selected');
			return;
		}
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			foreach ($criteriaIds as $criterionId) {
				$pdo->prepare('DELETE FROM criteria WHERE id = ?')->execute([$criterionId]);
			}
			$pdo->commit();
			redirect('/subcategories/' . $subcategoryId . '/criteria?success=criteria_deleted');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/subcategories/' . $subcategoryId . '/criteria?error=delete_failed');
		}
	}
	
	public function bulkUpdate(array $params): void {
		require_organizer();
		$subcategoryId = param('id', $params);
		$updates = post('updates', []);
		
		if (empty($updates)) {
			redirect('/subcategories/' . $subcategoryId . '/criteria?error=no_updates');
			return;
		}
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			foreach ($updates as $criterionId => $data) {
				if (isset($data['max_score'])) {
					$pdo->prepare('UPDATE criteria SET max_score = ? WHERE id = ?')
						->execute([(int)$data['max_score'], $criterionId]);
				}
			}
			$pdo->commit();
			redirect('/subcategories/' . $subcategoryId . '/criteria?success=criteria_updated');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/subcategories/' . $subcategoryId . '/criteria?error=update_failed');
		}
	}
}

// Organizer can set score cap per subcategory
class SubcategoryAdminController {
	public function edit(array $params): void {
		require_organizer();
		$subcategoryId = param('id', $params);
		$stmt = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.id = ?');
		$stmt->execute([$subcategoryId]);
		$subcategory = $stmt->fetch(\PDO::FETCH_ASSOC);
		view('subcategories/admin_edit', compact('subcategory'));
	}
	public function update(array $params): void {
		require_organizer();
		$subcategoryId = param('id', $params);
		$cap = (int)post('score_cap');
		DB::pdo()->prepare('UPDATE subcategories SET score_cap=? WHERE id=?')->execute([$cap, $subcategoryId]);
		redirect('/categories/' . (string)post('category_id') . '/subcategories');
	}
}

class ScoringController {
	public function scoreContestant(array $params): void {
		require_login();
		$subcategoryId = param('subcategoryId', $params);
		$contestantId = param('contestantId', $params);
		
		if (is_judge()) {
			$judgeId = current_user()['judge_id'] ?? '';
			if (empty($judgeId)) {
				http_response_code(403); 
				echo 'Judge account not properly linked. Please contact administrator.'; 
				return;
			}
			
			// Verify judge is assigned to this subcategory
			$stmt = DB::pdo()->prepare('SELECT COUNT(*) FROM subcategory_judges WHERE subcategory_id = ? AND judge_id = ?');
			$stmt->execute([$subcategoryId, $judgeId]);
			if ($stmt->fetchColumn() == 0) {
				http_response_code(403);
				echo 'You are not assigned to this subcategory.';
				return;
			}
		}
		
		// Get subcategory info
		$stmt = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.id = ?');
		$stmt->execute([$subcategoryId]);
		$subcategory = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$subcategory) {
			http_response_code(404);
			echo 'Subcategory not found.';
			return;
		}
		
		// Get contestant info
		$stmt = DB::pdo()->prepare('SELECT * FROM contestants WHERE id = ?');
		$stmt->execute([$contestantId]);
		$contestant = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$contestant) {
			http_response_code(404);
			echo 'Contestant not found.';
			return;
		}
		
		// Get criteria for this subcategory
		$stmt = DB::pdo()->prepare('SELECT * FROM criteria WHERE subcategory_id = ? ORDER BY name');
		$stmt->execute([$subcategoryId]);
		$criteria = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get existing scores for this judge and contestant
		$existingScores = [];
		if (is_judge()) {
			$stmt = DB::pdo()->prepare('SELECT criterion_id, score FROM scores WHERE subcategory_id = ? AND contestant_id = ? AND judge_id = ?');
			$stmt->execute([$subcategoryId, $contestantId, $judgeId]);
			$existingScores = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
		}
		
		// Get existing comment for this judge and contestant
		$existingComment = '';
		if (is_judge()) {
			$stmt = DB::pdo()->prepare('SELECT comment FROM judge_comments WHERE subcategory_id = ? AND contestant_id = ? AND judge_id = ?');
			$stmt->execute([$subcategoryId, $contestantId, $judgeId]);
			$existingComment = $stmt->fetchColumn() ?: '';
		}
		
		// Check if scores are certified for this specific contestant
		$isCertified = false;
		if (is_judge()) {
			$stmt = DB::pdo()->prepare('SELECT COUNT(*) FROM judge_certifications WHERE subcategory_id = ? AND contestant_id = ? AND judge_id = ?');
			$stmt->execute([$subcategoryId, $contestantId, $judgeId]);
			$isCertified = $stmt->fetchColumn() > 0;
		}
		
		view('scoring/contestant', compact('subcategory', 'contestant', 'criteria', 'existingScores', 'existingComment', 'isCertified'));
	}

	public function index(array $params): void {
		require_login();
		$subcategoryId = param('id', $params);
		if (is_judge()) {
			$judgeId = current_user()['judge_id'] ?? '';
			if (empty($judgeId)) {
				http_response_code(403); 
				echo 'Judge account not properly linked. Please contact administrator.'; 
				return;
			}
			$allowed = DB::pdo()->prepare('SELECT 1 FROM subcategory_judges WHERE subcategory_id = ? AND judge_id = ?');
			$allowed->execute([$subcategoryId, $judgeId]);
			if (!$allowed->fetchColumn()) { 
				http_response_code(403); 
				echo 'You are not assigned to judge this subcategory.'; 
				return; 
			}
		}
		$subcategory = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.id = ?');
		$subcategory->execute([$subcategoryId]);
		$subcategory = $subcategory->fetch(\PDO::FETCH_ASSOC);
		// Lock if certified and not organizer
		$locked = false;
		if (is_judge()) {
			$chk = DB::pdo()->prepare('SELECT 1 FROM judge_certifications WHERE subcategory_id=? AND judge_id=?');
			$chk->execute([$subcategoryId, current_user()['judge_id'] ?? '']);
			$locked = (bool)$chk->fetchColumn();
		}
		$contestants = DB::pdo()->prepare('SELECT con.* FROM subcategory_contestants sc JOIN contestants con ON sc.contestant_id = con.id WHERE sc.subcategory_id = ? ORDER BY con.name');
		$contestants->execute([$subcategoryId]);
		$contestants = $contestants->fetchAll(\PDO::FETCH_ASSOC);
		$criteria = DB::pdo()->prepare('SELECT * FROM criteria WHERE subcategory_id = ? ORDER BY name');
		$criteria->execute([$subcategoryId]);
		$criteria = $criteria->fetchAll(\PDO::FETCH_ASSOC);
		$judges = DB::pdo()->prepare('SELECT j.* FROM subcategory_judges sj JOIN judges j ON sj.judge_id = j.id WHERE sj.subcategory_id = ? ORDER BY j.name');
		$judges->execute([$subcategoryId]);
		$judges = $judges->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get existing scores for this judge if they're a judge
		$existingScores = [];
		if (is_judge()) {
			$judgeId = current_user()['judge_id'] ?? '';
			$stmt = DB::pdo()->prepare('SELECT contestant_id, criterion_id, score FROM scores WHERE subcategory_id = ? AND judge_id = ?');
			$stmt->execute([$subcategoryId, $judgeId]);
			$existingScores = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		}
		
		view('scoring/index', compact('subcategory','contestants','criteria','judges','locked','existingScores'));
	}
	public function submit(array $params): void {
		require_login();
		$subcategoryId = param('id', $params);
		$contestantId = post('contestant_id');
		$judgeId = is_judge() ? (current_user()['judge_id'] ?? '') : post('judge_id');
		
		// Debug log score submission attempt
		\App\Logger::debug('score_submission_attempt', 'score', null, 
			"Attempting to submit scores: subcategory_id={$subcategoryId}, contestant_id={$contestantId}, judge_id={$judgeId}");
		
		if (is_judge()) {
			$allowed = DB::pdo()->prepare('SELECT 1 FROM subcategory_judges WHERE subcategory_id = ? AND judge_id = ?');
			$allowed->execute([$subcategoryId, $judgeId]);
			if (!$allowed->fetchColumn()) { 
				\App\Logger::debug('score_submission_failed', 'score', null, 
					"Score submission failed: judge not assigned to subcategory");
				http_response_code(403); 
				echo 'Forbidden'; 
				return; 
			}
			// Prevent edits after certification for this specific contestant
			$chk = DB::pdo()->prepare('SELECT 1 FROM judge_certifications WHERE subcategory_id=? AND contestant_id=? AND judge_id=?');
			$chk->execute([$subcategoryId, $contestantId, $judgeId]);
			if ($chk->fetchColumn()) { 
				\App\Logger::debug('score_submission_failed', 'score', null, 
					"Score submission failed: scores are locked/certified");
				http_response_code(423); 
				echo 'Locked'; 
				return; 
			}
		}
		$scores = $_POST['scores'] ?? [];
		$comments = $_POST['comments'] ?? [];
		
		\App\Logger::debug('score_submission_data', 'score', null, 
			"Score submission data: " . json_encode(['scores' => $scores, 'comments' => $comments]));
		
		$pdo = DB::pdo();
		$now = date('c');
		$pdo->beginTransaction();
		
		// Handle scores - they come as scores[criterion_id] = value
		foreach ($scores as $criterionId => $value) {
			if ($value !== '' && $value !== null) {
				$scoreId = uuid();
				$stmt = $pdo->prepare('INSERT OR REPLACE INTO scores (id, subcategory_id, contestant_id, judge_id, criterion_id, score, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
				$stmt->execute([$scoreId, $subcategoryId, $contestantId, $judgeId, $criterionId, (float)$value, $now]);
				\App\Logger::debug('score_submitted', 'score', $scoreId, 
					"Score submitted: criterion_id={$criterionId}, score={$value}, contestant_id={$contestantId}, judge_id={$judgeId}");
			}
		}
		
		// Handle comments - they come as comments[contestant_id] = text
		foreach ($comments as $commentContestantId => $text) {
			if ($text !== '' && $text !== null) {
				$stmt = $pdo->prepare('INSERT OR REPLACE INTO judge_comments (id, subcategory_id, contestant_id, judge_id, comment, created_at) VALUES (?, ?, ?, ?, ?, ?)');
				$stmt->execute([uuid(), $subcategoryId, $commentContestantId, $judgeId, (string)$text, $now]);
			}
		}
		
		// Commit scores and comments first - these should always be saved
		$pdo->commit();
		
		// Handle certification separately - this can fail without affecting scores
		if (is_judge()) {
			$signature = trim((string)post('signature_name'));
			if ($signature === '') { 
				// Use preferred name as default signature
				$signature = current_user()['preferred_name'] ?? current_user()['name'];
			}
			
			// Validate signature matches judge's preferred name
			$judgePreferredName = current_user()['preferred_name'] ?? current_user()['name'];
			if (strtolower(trim($signature)) !== strtolower(trim($judgePreferredName))) {
				// Scores are already saved, just redirect with error about certification
				redirect('/score/' . $subcategoryId . '/contestant/' . $contestantId . '?error=signature_mismatch&scores_saved=1');
				return;
			}
			
			// Add certification in a separate transaction
			$pdo->beginTransaction();
			$pdo->prepare('INSERT OR REPLACE INTO judge_certifications (id, subcategory_id, contestant_id, judge_id, signature_name, certified_at) VALUES (?,?,?,?,?,?)')
				->execute([uuid(), $subcategoryId, $contestantId, $judgeId, $signature, $now]);
			$pdo->commit();
		}
		
		// Log the scoring action
		$scoreCount = count($scores);
		\App\Logger::logScoreSubmission($subcategoryId, '', $judgeId, $scoreCount);
		
		// Add success message to session
		$_SESSION['success_message'] = 'Scores submitted successfully!';
		redirect('/score/' . $subcategoryId);
	}
	
	public function unsign(array $params): void {
		require_organizer(); // Only organizers can unsign scores
		$subcategoryId = param('id', $params);
		$judgeId = post('judge_id');
		
		if (empty($judgeId)) {
			redirect('/score/' . $subcategoryId . '?error=no_judge');
			return;
		}
		
		// Remove the certification
		$pdo = DB::pdo();
		$pdo->prepare('DELETE FROM judge_certifications WHERE subcategory_id = ? AND judge_id = ?')
			->execute([$subcategoryId, $judgeId]);
		
		\App\Logger::logAdminAction('unsign_scores', 'subcategory', $subcategoryId, "Judge: $judgeId");
		
		$_SESSION['success_message'] = 'Judge scores have been unsigned successfully!';
		redirect('/score/' . $subcategoryId);
	}
}

class ResultsController {
	public function resultsIndex(): void {
		require_login();
		
		// Get subcategories based on user role
		if (is_organizer()) {
			// Organizers can see all subcategories
			$stmt = DB::pdo()->query('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id ORDER BY c.name, s.name');
			$subcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		} elseif (is_judge()) {
			// Judges can only see their assigned subcategories
			$judgeId = current_user()['judge_id'] ?? '';
			$stmt = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id JOIN subcategory_judges sj ON s.id = sj.subcategory_id WHERE sj.judge_id = ? ORDER BY c.name, s.name');
			$stmt->execute([$judgeId]);
			$subcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		} else {
			// Other users see no results
			$subcategories = [];
		}
		
		// Get results for each subcategory
		$results = [];
		foreach ($subcategories as $subcategory) {
			$stmt = DB::pdo()->prepare('SELECT sc.contestant_id as contestantId, con.name as contestantName, SUM(s.score) as totalScore FROM scores s JOIN contestants con ON con.id = s.contestant_id JOIN subcategory_contestants sc ON sc.contestant_id = s.contestant_id AND sc.subcategory_id = s.subcategory_id WHERE s.subcategory_id = ? GROUP BY sc.contestant_id, con.name ORDER BY totalScore DESC');
			$stmt->execute([$subcategory['id']]);
			$subcategoryResults = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			if (!empty($subcategoryResults)) {
				$results[$subcategory['id']] = [
					'subcategory' => $subcategory,
					'results' => $subcategoryResults
				];
			}
		}
		
		view('results/all', compact('subcategories', 'results'));
	}

	public function index(array $params): void {
		require_login();
		$subcategoryId = param('id', $params);
		$subcategory = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.id = ?');
		$subcategory->execute([$subcategoryId]);
		$subcategory = $subcategory->fetch(\PDO::FETCH_ASSOC);
		$agg = DB::pdo()->prepare('SELECT sc.contestant_id as contestantId, con.name as contestantName, SUM(s.score) as totalScore FROM scores s JOIN contestants con ON con.id = s.contestant_id JOIN subcategory_contestants sc ON sc.contestant_id = s.contestant_id AND sc.subcategory_id = s.subcategory_id WHERE s.subcategory_id = ? GROUP BY sc.contestant_id, con.name ORDER BY totalScore DESC');
		$agg->execute([$subcategoryId]);
		$results = $agg->fetchAll(\PDO::FETCH_ASSOC);
		view('results/index', compact('subcategory','results'));
	}
	public function categoryIndex(): void {
		require_login();
		
		// Get categories based on user role
		if (is_organizer()) {
			// Organizers can see all categories
			$categories = DB::pdo()->query('SELECT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id ORDER BY co.name, c.name')->fetchAll(\PDO::FETCH_ASSOC);
			$subcategories = DB::pdo()->query('SELECT sc.*, c.name as category_name FROM subcategories sc JOIN categories c ON sc.category_id = c.id ORDER BY c.name, sc.name')->fetchAll(\PDO::FETCH_ASSOC);
		} elseif (is_judge()) {
			// Judges can only see categories with their assigned subcategories
			$judgeId = current_user()['judge_id'] ?? '';
			$stmt = DB::pdo()->prepare('SELECT DISTINCT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id JOIN subcategories sc ON sc.category_id = c.id JOIN subcategory_judges sj ON sc.id = sj.subcategory_id WHERE sj.judge_id = ? ORDER BY co.name, c.name');
			$stmt->execute([$judgeId]);
			$categories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			$stmt = DB::pdo()->prepare('SELECT sc.*, c.name as category_name FROM subcategories sc JOIN categories c ON sc.category_id = c.id JOIN subcategory_judges sj ON sc.id = sj.subcategory_id WHERE sj.judge_id = ? ORDER BY c.name, sc.name');
			$stmt->execute([$judgeId]);
			$subcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		} else {
			// Other users see no categories
			$categories = [];
			$subcategories = [];
		}
		
		// Aggregate results by category and contestant
		$sql = "SELECT c.id as categoryId, c.name as categoryName, con.id as contestantId, con.name as contestantName, con.contestant_number, SUM(s.score) as totalScore
		FROM categories c
		JOIN subcategories sc ON sc.category_id = c.id
		JOIN scores s ON s.subcategory_id = sc.id
		JOIN contestants con ON con.id = s.contestant_id
		GROUP BY c.id, c.name, con.id, con.name, con.contestant_number
		ORDER BY c.name, totalScore DESC";
		
		try {
			$rows = DB::pdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
		} catch (\PDOException $e) {
			// If no scores exist yet, return empty results
			$rows = [];
		}
		
		// Calculate lead contestants for admins
		$leadContestants = [];
		if (is_organizer()) {
			foreach ($categories as $category) {
				$categoryResults = array_filter($rows, function($row) use ($category) {
					return $row['categoryId'] === $category['id'];
				});
				if (!empty($categoryResults)) {
					$leadContestants[$category['id']] = reset($categoryResults);
				}
			}
		}
		
		view('results/categories', compact('rows','categories','subcategories','leadContestants'));
	}
	public function detailed(array $params): void {
		require_login();
		$subcategoryId = param('id', $params);
		$subcategory = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.id = ?');
		$subcategory->execute([$subcategoryId]);
		$subcategory = $subcategory->fetch(\PDO::FETCH_ASSOC);
		
		// Get all contestants and their scores by judge and criterion
		$contestants = DB::pdo()->prepare('SELECT con.* FROM subcategory_contestants sc JOIN contestants con ON sc.contestant_id = con.id WHERE sc.subcategory_id = ? ORDER BY con.name');
		$contestants->execute([$subcategoryId]);
		$contestants = $contestants->fetchAll(\PDO::FETCH_ASSOC);
		
		$criteria = DB::pdo()->prepare('SELECT * FROM criteria WHERE subcategory_id = ? ORDER BY name');
		$criteria->execute([$subcategoryId]);
		$criteria = $criteria->fetchAll(\PDO::FETCH_ASSOC);
		
		$judges = DB::pdo()->prepare('SELECT j.* FROM subcategory_judges sj JOIN judges j ON sj.judge_id = j.id WHERE sj.subcategory_id = ? ORDER BY j.name');
		$judges->execute([$subcategoryId]);
		$judges = $judges->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get all scores
		$scores = DB::pdo()->prepare('SELECT * FROM scores WHERE subcategory_id = ?');
		$scores->execute([$subcategoryId]);
		$scores = $scores->fetchAll(\PDO::FETCH_ASSOC);

		// Get overall deductions for this subcategory keyed by contestant
		$dedStmt = DB::pdo()->prepare('SELECT contestant_id, SUM(amount) as total_deduction FROM overall_deductions WHERE subcategory_id = ? GROUP BY contestant_id');
		$dedStmt->execute([$subcategoryId]);
		$deductions = [];
		foreach ($dedStmt->fetchAll(\PDO::FETCH_ASSOC) as $row) { $deductions[$row['contestant_id']] = (float)$row['total_deduction']; }
		
		// Get comments
		$comments = DB::pdo()->prepare('SELECT * FROM judge_comments WHERE subcategory_id = ?');
		$comments->execute([$subcategoryId]);
		$comments = $comments->fetchAll(\PDO::FETCH_ASSOC);
		
		view('results/detailed', compact('subcategory','contestants','criteria','judges','scores','comments','deductions'));
	}

	public function addDeduction(array $params): void {
		require_login();
		$subcategoryId = param('subcategoryId', $params);
		$contestantId = param('contestantId', $params);
        $amount = (float)($_POST['amount'] ?? 0);
        $comment = trim((string)($_POST['comment'] ?? '')) ?: null;
        $signature = trim((string)($_POST['signature'] ?? '')) ?: null;

		$allowed = is_organizer();
		if (!$allowed && is_judge()) {
			$jid = $_SESSION['user']['judge_id'] ?? null;
			if ($jid) {
				$st = DB::pdo()->prepare('SELECT is_head_judge FROM judges WHERE id = ?');
				$st->execute([$jid]);
				$row = $st->fetch(\PDO::FETCH_ASSOC);
				$allowed = !empty($row) && (int)$row['is_head_judge'] === 1;
			}
		}
		if (!$allowed) { http_response_code(403); echo 'Forbidden'; return; }

        if ($amount <= 0) { $_SESSION['success_message'] = 'Deduction must be greater than 0.'; redirect('/results/' . $subcategoryId . '/detailed'); return; }
        if (!$comment) { $_SESSION['success_message'] = 'Deduction comment is required.'; redirect('/results/' . $subcategoryId . '/detailed'); return; }
        // Signature required, and if judge, must match preferred_name
        if (!$signature) { $_SESSION['success_message'] = 'Signature required for deductions.'; redirect('/results/' . $subcategoryId . '/detailed'); return; }
        if (is_judge()) {
            $user = $_SESSION['user'] ?? [];
            $pref = $user['preferred_name'] ?? $user['name'] ?? '';
            if (strcasecmp($pref, $signature) !== 0) {
                $_SESSION['success_message'] = 'Signature must match your preferred name.'; redirect('/results/' . $subcategoryId . '/detailed'); return;
            }
        }
		$uid = $_SESSION['user']['id'] ?? null;
        DB::pdo()->prepare('INSERT INTO overall_deductions (id, subcategory_id, contestant_id, amount, comment, signature_name, signed_at, created_by) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([uuid(), $subcategoryId, $contestantId, $amount, $comment, $signature, date('c'), $uid]);
		\App\Logger::logAdminAction('add_overall_deduction', 'subcategory', $subcategoryId, 'Contestant ' . $contestantId . ' amount ' . $amount);
		$_SESSION['success_message'] = 'Deduction added.';
		redirect('/results/' . $subcategoryId . '/detailed');
	}

	public function contestantDetailed(array $params): void {
		require_organizer(); // Only organizers can view detailed contestant scores
		$contestantId = param('contestantId', $params);
		$categoryId = param('categoryId', $params);
		
		// Get contestant info
		$contestant = DB::pdo()->prepare('SELECT * FROM contestants WHERE id = ?');
		$contestant->execute([$contestantId]);
		$contestant = $contestant->fetch(\PDO::FETCH_ASSOC);
		
		// Get category info
		$category = DB::pdo()->prepare('SELECT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id WHERE c.id = ?');
		$category->execute([$categoryId]);
		$category = $category->fetch(\PDO::FETCH_ASSOC);
		
		// Get subcategories for this category
		$subcategories = DB::pdo()->prepare('SELECT * FROM subcategories WHERE category_id = ? ORDER BY name');
		$subcategories->execute([$categoryId]);
		$subcategories = $subcategories->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get all scores for this contestant in this category
		$scores = DB::pdo()->prepare('
			SELECT s.*, sc.name as subcategory_name, c.name as criterion_name, c.max_score, j.name as judge_name
			FROM scores s 
			JOIN subcategories sc ON s.subcategory_id = sc.id 
			JOIN criteria c ON s.criterion_id = c.id 
			JOIN judges j ON s.judge_id = j.id 
			WHERE s.contestant_id = ? AND sc.category_id = ?
			ORDER BY sc.name, c.name, j.name
		');
		$scores->execute([$contestantId, $categoryId]);
		$scores = $scores->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get comments for this contestant in this category
		$comments = DB::pdo()->prepare('
			SELECT jc.*, sc.name as subcategory_name, j.name as judge_name
			FROM judge_comments jc 
			JOIN subcategories sc ON jc.subcategory_id = sc.id 
			JOIN judges j ON jc.judge_id = j.id 
			WHERE jc.contestant_id = ? AND sc.category_id = ?
			ORDER BY sc.name, j.name
		');
		$comments->execute([$contestantId, $categoryId]);
		$comments = $comments->fetchAll(\PDO::FETCH_ASSOC);
		
		view('results/contestant_detailed', compact('contestant','category','subcategories','scores','comments'));
	}
	
	public function unsignAll(array $params): void {
		require_organizer(); // Only organizers can unsign scores
		$subcategoryId = param('id', $params);
		
		// Remove all certifications for this subcategory
		$pdo = DB::pdo();
		$pdo->prepare('DELETE FROM judge_certifications WHERE subcategory_id = ?')
			->execute([$subcategoryId]);
		
		$_SESSION['success_message'] = 'All scores for this subcategory have been unsigned successfully!';
		redirect('/results/' . $subcategoryId);
	}
	
	public function unsignAllByCategory(array $params): void {
		require_organizer(); // Only organizers can unsign scores
		$categoryId = param('categoryId', $params);
		
		// Get all subcategories for this category
		$subcategories = DB::pdo()->prepare('SELECT id FROM subcategories WHERE category_id = ?');
		$subcategories->execute([$categoryId]);
		$subcategoryIds = $subcategories->fetchAll(\PDO::FETCH_COLUMN);
		
		// Remove all certifications for all subcategories in this category
		$pdo = DB::pdo();
		foreach ($subcategoryIds as $subcategoryId) {
			$pdo->prepare('DELETE FROM judge_certifications WHERE subcategory_id = ?')
				->execute([$subcategoryId]);
		}
		
		$_SESSION['success_message'] = 'All scores for this category have been unsigned successfully!';
		redirect('/results');
	}
	
	public function unsignAllByContestant(array $params): void {
		require_organizer(); // Only organizers can unsign scores
		$contestantId = param('contestantId', $params);
		
		// Get all subcategories this contestant is assigned to
		$subcategories = DB::pdo()->prepare('SELECT subcategory_id FROM subcategory_contestants WHERE contestant_id = ?');
		$subcategories->execute([$contestantId]);
		$subcategoryIds = $subcategories->fetchAll(\PDO::FETCH_COLUMN);
		
		// Remove all certifications for all subcategories this contestant is in
		$pdo = DB::pdo();
		foreach ($subcategoryIds as $subcategoryId) {
			$pdo->prepare('DELETE FROM judge_certifications WHERE subcategory_id = ?')
				->execute([$subcategoryId]);
		}
		
		$_SESSION['success_message'] = 'All scores for this contestant have been unsigned successfully!';
		redirect('/results');
	}
	
	public function unsignAllByJudge(array $params): void {
		require_organizer(); // Only organizers can unsign scores
		$judgeId = param('judgeId', $params);
		
		// Remove all certifications for this judge
		$pdo = DB::pdo();
		$pdo->prepare('DELETE FROM judge_certifications WHERE judge_id = ?')
			->execute([$judgeId]);
		
		$_SESSION['success_message'] = 'All scores for this judge have been unsigned successfully!';
		redirect('/results');
	}

	public function contestantsIndex(): void {
		require_login();
		if (!is_organizer() && !is_judge()) { http_response_code(403); echo 'Forbidden'; return; }
		// If judge, filter to contestants in judge's assigned categories
		$rows = [];
		if (is_organizer()) {
			$rows = DB::pdo()->query('SELECT id, name, contestant_number FROM contestants ORDER BY contestant_number IS NULL, contestant_number, name')->fetchAll(\PDO::FETCH_ASSOC);
		} else {
			$jid = $_SESSION['user']['judge_id'] ?? null;
			if ($jid) {
				$stmt = DB::pdo()->prepare('SELECT DISTINCT con.id, con.name, con.contestant_number
					FROM contestants con
					JOIN category_contestants cc ON cc.contestant_id = con.id
					JOIN category_judges cj ON cj.category_id = cc.category_id AND cj.judge_id = ?
					ORDER BY con.contestant_number IS NULL, con.contestant_number, con.name');
				$stmt->execute([$jid]);
				$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			}
		}
		view('results/contestants_index', compact('rows'));
	}

	public function contestantOverview(array $params): void {
		require_login();
		$contestantId = param('id', $params);
		// Get contestant
		$stmt = DB::pdo()->prepare('SELECT * FROM contestants WHERE id = ?');
		$stmt->execute([$contestantId]);
		$contestant = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$contestant) { http_response_code(404); echo 'Not found'; return; }
		// Categories and subcategories with scores
		$subs = DB::pdo()->prepare('SELECT sub.*, cat.name as category_name FROM subcategories sub JOIN categories cat ON sub.category_id = cat.id WHERE EXISTS (SELECT 1 FROM subcategory_contestants sc WHERE sc.subcategory_id = sub.id AND sc.contestant_id = ?) ORDER BY cat.name, sub.name');
		$subs->execute([$contestantId]);
		$subcategories = $subs->fetchAll(\PDO::FETCH_ASSOC);
		// Scores per subcategory
		$scoresStmt = DB::pdo()->prepare('SELECT s.*, cr.name as criterion_name, j.name as judge_name FROM scores s JOIN criteria cr ON s.criterion_id = cr.id JOIN judges j ON j.id = s.judge_id WHERE s.contestant_id = ?');
		$scoresStmt->execute([$contestantId]);
		$scores = $scoresStmt->fetchAll(\PDO::FETCH_ASSOC);
		// Deductions per subcategory
		$ded = DB::pdo()->prepare('SELECT subcategory_id, SUM(amount) as total FROM overall_deductions WHERE contestant_id = ? GROUP BY subcategory_id');
		$ded->execute([$contestantId]);
		$deductions = [];
		foreach ($ded->fetchAll(\PDO::FETCH_ASSOC) as $row) { $deductions[$row['subcategory_id']] = (float)$row['total']; }
		
		view('results/contestant_overview', compact('contestant','subcategories','scores','deductions'));
	}
}

class AuthController {
	public function loginForm(): void {
		if (is_logged_in()) {
			redirect('/');
			return;
		}
		view('auth/login');
	}
	
	public function login(): void {
		$email = post('email');
		$password = post('password');
		
		// Debug log login attempt
		\App\Logger::debug('login_attempt', 'auth', null, 
			"Login attempt: email={$email}");
		
		if (empty($email) || empty($password)) {
			\App\Logger::debug('login_failed', 'auth', null, 
				"Login failed: missing email or password");
			redirect('/login?error=missing_fields');
			return;
		}
		
		try {
			// Try to find user by email or preferred name
			$stmt = DB::pdo()->prepare('SELECT * FROM users WHERE email = ? OR preferred_name = ?');
			$stmt->execute([$email, $email]);
			$user = $stmt->fetch(\PDO::FETCH_ASSOC);
			
			if (!$user || !password_verify($password, $user['password_hash'])) {
				\App\Logger::debug('login_failed', 'auth', null, 
					"Login failed: invalid credentials for email={$email}");
				\App\Logger::logLogin($email, false);
				redirect('/login?error=invalid_credentials');
				return;
			}
			
			// Check if user's session has been invalidated
			// This check is only relevant if the user is already logged in
			// For fresh logins, we don't need to check session version mismatch
			if (isset($_SESSION['user']) && isset($_SESSION['session_version'])) {
				$dbSessionVersion = $user['session_version'] ?? '1';
				$sessionVersion = $_SESSION['session_version'] ?? '1';
				if ($dbSessionVersion !== $sessionVersion) {
					\App\Logger::debug('login_failed', 'auth', $user['id'] ?? null, 
						"Login failed: session invalidated for user={$user['email']}");
					\App\Logger::logLogin($user['email'] ?? $user['preferred_name'], false, 'session_invalidated');
					redirect('/login?error=session_invalidated');
					return;
				}
			}
			
			$_SESSION['user'] = $user;
			$_SESSION['session_version'] = $user['session_version'];
			
			// Update last_login timestamp
			$now = date('c');
			$stmt = DB::pdo()->prepare('UPDATE users SET last_login = ? WHERE id = ?');
			$stmt->execute([$now, $user['id']]);
			
			\App\Logger::debug('login_successful', 'auth', $user['id'], 
				"Login successful: user_id={$user['id']}, email={$user['email']}, role={$user['role']}");
			\App\Logger::logLogin($user['email'] ?? $user['preferred_name'], true);
			
			// Redirect based on role
			if ($user['role'] === 'organizer') {
				redirect('/admin');
			} elseif ($user['role'] === 'judge') {
				redirect('/judge');
			} elseif ($user['role'] === 'emcee') {
				redirect('/emcee');
			} else {
				redirect('/');
			}
		} catch (\Exception $e) {
			// Log the error and redirect to login with error
			error_log('Login error: ' . $e->getMessage());
			\App\Logger::logLogin($email, false, 'database_error');
			redirect('/login?error=database_error');
		}
	}
	
	public function logout(): void {
		if (isset($_SESSION['user'])) {
			$user = $_SESSION['user'];
			\App\Logger::debug('logout_attempt', 'auth', $user['id'], 
				"Logout attempt: user_id={$user['id']}, email={$user['email']}, role={$user['role']}");
			\App\Logger::logLogout($user['email'] ?? $user['preferred_name']);
		}
		session_destroy();
		redirect('/');
	}
	
	public function judgeDashboard(): void {
		require_login();
		if (!is_judge()) {
			http_response_code(403);
			echo 'Forbidden';
			return;
		}
		
		$judgeId = current_user()['judge_id'] ?? '';
		if (empty($judgeId)) {
			http_response_code(403);
			echo 'Judge account not properly linked. Please contact administrator.';
			return;
		}
		
		// Get assigned subcategories for this judge
		$stmt = DB::pdo()->prepare('
			SELECT DISTINCT s.*, c.name as category_name 
			FROM subcategories s 
			JOIN categories c ON s.category_id = c.id 
			JOIN subcategory_judges sj ON s.id = sj.subcategory_id 
			WHERE sj.judge_id = ? 
			ORDER BY c.name, s.name
		');
		$stmt->execute([$judgeId]);
		$subcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		view('auth/judge', compact('subcategories'));
	}
	
	public function judgeSubcategoryContestants(array $params): void {
		require_login();
		if (!is_judge()) {
			http_response_code(403);
			echo 'Forbidden';
			return;
		}
		
		$subcategoryId = param('id', $params);
		$judgeId = current_user()['judge_id'] ?? '';
		
		// Verify judge is assigned to this subcategory
		$stmt = DB::pdo()->prepare('SELECT COUNT(*) FROM subcategory_judges WHERE subcategory_id = ? AND judge_id = ?');
		$stmt->execute([$subcategoryId, $judgeId]);
		if ($stmt->fetchColumn() == 0) {
			http_response_code(403);
			echo 'You are not assigned to this subcategory.';
			return;
		}
		
		// Get subcategory info
		$stmt = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id WHERE s.id = ?');
		$stmt->execute([$subcategoryId]);
		$subcategory = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		// Get contestants assigned to this subcategory
		$stmt = DB::pdo()->prepare('
			SELECT con.* 
			FROM contestants con 
			JOIN subcategory_contestants sc ON con.id = sc.contestant_id 
			WHERE sc.subcategory_id = ? 
			ORDER BY con.contestant_number IS NULL, con.contestant_number, con.name
		');
		$stmt->execute([$subcategoryId]);
		$contestants = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		view('auth/judge_contestants', compact('subcategory', 'contestants'));
	}
}

class UserController {
	public function new(): void {
		require_organizer();
		$categories = DB::pdo()->query('SELECT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id ORDER BY co.name, c.name')->fetchAll(\PDO::FETCH_ASSOC);
		view('users/new', compact('categories'));
	}
	
	public function create(): void {
		require_organizer();
		
		$name = post('name');
		$email = post('email') ?: null;
		$password = post('password');
		$role = post('role');
		$preferredName = post('preferred_name') ?: $name;
		$gender = post('gender') ?: null;
		$categoryId = post('category_id') ?: null;
		$isHeadJudge = post('is_head_judge') ? 1 : 0;
		
		// Debug log user creation attempt
		\App\Logger::debug('user_creation_attempt', 'user', null, 
			"Attempting to create user: name={$name}, email={$email}, role={$role}, category_id={$categoryId}, is_head_judge={$isHeadJudge}");
		
		// Validate required fields
		if (empty($name) || empty($role)) {
			\App\Logger::debug('user_creation_validation_failed', 'user', null, 
				"User creation failed validation: missing name or role");
			redirect('/users/new?error=missing_fields');
			return;
		}
		
		// Validate password complexity if provided
		if (!empty($password)) {
			if (strlen($password) < 8) {
				\App\Logger::debug('user_creation_password_failed', 'user', null, 
					"User creation failed: password too short");
				redirect('/users/new?error=password_too_short');
				return;
			}
			if (!preg_match('/[A-Z]/', $password)) {
				\App\Logger::debug('user_creation_password_failed', 'user', null, 
					"User creation failed: password missing uppercase");
				redirect('/users/new?error=password_no_uppercase');
				return;
			}
			if (!preg_match('/[a-z]/', $password)) {
				\App\Logger::debug('user_creation_password_failed', 'user', null, 
					"User creation failed: password missing lowercase");
				redirect('/users/new?error=password_no_lowercase');
				return;
			}
			if (!preg_match('/[0-9]/', $password)) {
				\App\Logger::debug('user_creation_password_failed', 'user', null, 
					"User creation failed: password missing number");
				redirect('/users/new?error=password_no_number');
				return;
			}
			if (!preg_match('/[^A-Za-z0-9]/', $password)) {
				\App\Logger::debug('user_creation_password_failed', 'user', null, 
					"User creation failed: password missing symbol");
				redirect('/users/new?error=password_no_symbol');
				return;
			}
		}
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			$userId = uuid();
			$passwordHash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
			
			// Create user
			$stmt = $pdo->prepare('INSERT INTO users (id, name, email, password_hash, role, preferred_name, gender) VALUES (?, ?, ?, ?, ?, ?, ?)');
			$stmt->execute([$userId, $name, $email, $passwordHash, $role, $preferredName, $gender]);
			\App\Logger::debug('user_created', 'user', $userId, 
				"User created successfully: name={$name}, email={$email}, role={$role}");
			
			// Handle role-specific creation
			if ($role === 'judge') {
				$judgeId = uuid();
				$stmt = $pdo->prepare('INSERT INTO judges (id, name, email, gender, is_head_judge) VALUES (?, ?, ?, ?, ?)');
				$stmt->execute([$judgeId, $name, $email, $gender, $isHeadJudge]);
				\App\Logger::debug('judge_created', 'judge', $judgeId, 
					"Judge created: name={$name}, email={$email}, is_head_judge={$isHeadJudge}");
				
				// Link user to judge
				$stmt = $pdo->prepare('UPDATE users SET judge_id = ? WHERE id = ?');
				$stmt->execute([$judgeId, $userId]);
				\App\Logger::debug('user_judge_linked', 'user', $userId, 
					"User linked to judge: user_id={$userId}, judge_id={$judgeId}");
				
				// Assign to category if provided
				if ($categoryId) {
					$stmt = $pdo->prepare('INSERT INTO category_judges (category_id, judge_id) VALUES (?, ?)');
					$stmt->execute([$categoryId, $judgeId]);
					\App\Logger::debug('judge_category_assigned', 'judge', $judgeId, 
						"Judge assigned to category: judge_id={$judgeId}, category_id={$categoryId}");
				}
			} elseif ($role === 'contestant') {
				$contestantId = uuid();
				$stmt = $pdo->prepare('INSERT INTO contestants (id, name, email, gender) VALUES (?, ?, ?, ?)');
				$stmt->execute([$contestantId, $name, $email, $gender]);
				\App\Logger::debug('contestant_created', 'contestant', $contestantId, 
					"Contestant created: name={$name}, email={$email}");
				
				// Link user to contestant
				$stmt = $pdo->prepare('UPDATE users SET contestant_id = ? WHERE id = ?');
				$stmt->execute([$contestantId, $userId]);
				\App\Logger::debug('user_contestant_linked', 'user', $userId, 
					"User linked to contestant: user_id={$userId}, contestant_id={$contestantId}");
				
				// Assign to category if provided
				if ($categoryId) {
					$stmt = $pdo->prepare('INSERT INTO category_contestants (category_id, contestant_id) VALUES (?, ?)');
					$stmt->execute([$categoryId, $contestantId]);
					\App\Logger::debug('contestant_category_assigned', 'contestant', $contestantId, 
						"Contestant assigned to category: contestant_id={$contestantId}, category_id={$categoryId}");
				}
			}
			
			$pdo->commit();
			
			// Log successful outcome
			\App\Logger::debug('user_creation_success', 'user', $userId, 
				"User creation completed successfully: user_id={$userId}, name={$name}, role={$role}");
			\App\Logger::logUserCreation($userId, $name, $role);
			
			redirect('/admin/users?success=user_created');
		} catch (\Exception $e) {
			$pdo->rollBack();
			
			// Log failure outcome
			\App\Logger::debug('user_creation_failed', 'user', null, 
				"User creation failed: " . $e->getMessage());
			\App\Logger::error('user_creation_failed', 'user', null, 
				"User creation failed: " . $e->getMessage());
			
			redirect('/users/new?error=creation_failed');
		}
	}
	
	public function index(): void {
		require_organizer();
		
		// Debug log data retrieval
		\App\Logger::debug('users_index_data_retrieval', 'users', null, 
			"Retrieving users with their associated contestant/judge data");
		
		$users = DB::pdo()->query('
			SELECT u.*, 
			       c.contestant_number,
			       j.is_head_judge
			FROM users u 
			LEFT JOIN contestants c ON u.contestant_id = c.id 
			LEFT JOIN judges j ON u.judge_id = j.id
			ORDER BY u.role, u.name
		')->fetchAll(\PDO::FETCH_ASSOC);
		
		// Group users by role
		$usersByRole = [];
		foreach ($users as $user) {
			$usersByRole[$user['role']][] = $user;
		}
		
		\App\Logger::debug('users_index_data_retrieved', 'users', null, 
			"Retrieved " . count($users) . " total users: " . 
			(count($usersByRole['organizer'] ?? []) . " organizers, " .
			count($usersByRole['judge'] ?? []) . " judges, " .
			count($usersByRole['contestant'] ?? []) . " contestants, " .
			count($usersByRole['emcee'] ?? []) . " emcees"));
		
		view('users/index', compact('usersByRole'));
	}
	
	public function edit(array $params): void {
		require_organizer();
		$id = param('id', $params);
		$stmt = DB::pdo()->prepare('SELECT * FROM users WHERE id = ?');
		$stmt->execute([$id]);
		$user = $stmt->fetch(\PDO::FETCH_ASSOC);
		if (!$user) {
			redirect('/admin/users');
			return;
		}
		view('users/edit', compact('user'));
	}
	
	public function update(array $params): void {
		require_organizer();
		$id = param('id', $params);
		$name = post('name');
		$email = post('email') ?: null;
		$password = post('password');
		$role = post('role');
		$preferredName = post('preferred_name') ?: $name;
		$gender = post('gender') ?: null;
		
		// Validate password complexity if provided
		if (!empty($password)) {
			if (strlen($password) < 8) {
				redirect('/admin/users/' . $id . '/edit?error=password_too_short');
				return;
			}
			if (!preg_match('/[A-Z]/', $password)) {
				redirect('/admin/users/' . $id . '/edit?error=password_no_uppercase');
				return;
			}
			if (!preg_match('/[a-z]/', $password)) {
				redirect('/admin/users/' . $id . '/edit?error=password_no_lowercase');
				return;
			}
			if (!preg_match('/[0-9]/', $password)) {
				redirect('/admin/users/' . $id . '/edit?error=password_no_number');
				return;
			}
			if (!preg_match('/[^A-Za-z0-9]/', $password)) {
				redirect('/admin/users/' . $id . '/edit?error=password_no_symbol');
				return;
			}
		}
		
		$passwordHash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
		
		if ($passwordHash) {
			$stmt = DB::pdo()->prepare('UPDATE users SET name = ?, email = ?, password_hash = ?, role = ?, preferred_name = ?, gender = ? WHERE id = ?');
			$stmt->execute([$name, $email, $passwordHash, $role, $preferredName, $gender, $id]);
		} else {
			$stmt = DB::pdo()->prepare('UPDATE users SET name = ?, email = ?, role = ?, preferred_name = ?, gender = ? WHERE id = ?');
			$stmt->execute([$name, $email, $role, $preferredName, $gender, $id]);
		}
		
		redirect('/admin/users?success=user_updated');
	}
	
	public function delete(array $params): void {
		require_organizer();
		$id = param('id', $params);
		
		// Debug log deletion attempt
		\App\Logger::debug('user_deletion_attempt', 'user', $id, 
			"Attempting to delete user: user_id={$id}");
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Get user info for logging
			$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
			$stmt->execute([$id]);
			$user = $stmt->fetch(\PDO::FETCH_ASSOC);
			
			if (!$user) {
				\App\Logger::debug('user_deletion_failed', 'user', $id, 
					"User deletion failed: user not found");
				redirect('/admin/users?error=user_not_found');
				return;
			}
			
			\App\Logger::debug('user_deletion_details', 'user', $id, 
				"User deletion details: name={$user['name']}, email={$user['email']}, role={$user['role']}");
			
			// Delete based on role
			if ($user['role'] === 'judge' && $user['judge_id']) {
				// Delete judge and all associated data
				$judgeId = $user['judge_id'];
				
				// Delete associated image file
				$stmt = $pdo->prepare('SELECT image_path FROM judges WHERE id = ?');
				$stmt->execute([$judgeId]);
				$imagePath = $stmt->fetchColumn();
				if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
					unlink(__DIR__ . '/../../public' . $imagePath);
				}
				
				$pdo->prepare('DELETE FROM judge_certifications WHERE judge_id = ?')->execute([$judgeId]);
				$pdo->prepare('DELETE FROM judge_comments WHERE judge_id = ?')->execute([$judgeId]);
				$pdo->prepare('DELETE FROM scores WHERE judge_id = ?')->execute([$judgeId]);
				$pdo->prepare('DELETE FROM subcategory_judges WHERE judge_id = ?')->execute([$judgeId]);
				$pdo->prepare('DELETE FROM category_judges WHERE judge_id = ?')->execute([$judgeId]);
				$pdo->prepare('DELETE FROM judges WHERE id = ?')->execute([$judgeId]);
			} elseif ($user['role'] === 'contestant' && $user['contestant_id']) {
				// Delete contestant and all associated data
				$contestantId = $user['contestant_id'];
				
				// Delete associated image file
				$stmt = $pdo->prepare('SELECT image_path FROM contestants WHERE id = ?');
				$stmt->execute([$contestantId]);
				$imagePath = $stmt->fetchColumn();
				if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
					unlink(__DIR__ . '/../../public' . $imagePath);
				}
				
				$pdo->prepare('DELETE FROM judge_comments WHERE contestant_id = ?')->execute([$contestantId]);
				$pdo->prepare('DELETE FROM scores WHERE contestant_id = ?')->execute([$contestantId]);
				$pdo->prepare('DELETE FROM subcategory_contestants WHERE contestant_id = ?')->execute([$contestantId]);
				$pdo->prepare('DELETE FROM category_contestants WHERE contestant_id = ?')->execute([$contestantId]);
				$pdo->prepare('DELETE FROM contestants WHERE id = ?')->execute([$contestantId]);
			}
			
			// Delete the user
			$pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
			
			$pdo->commit();
			
			// Log successful outcome
			\App\Logger::debug('user_deletion_success', 'user', $id, 
				"User deletion completed successfully: user_id={$id}, name={$user['name']}, role={$user['role']}");
			\App\Logger::logUserDeletion($id, $user['name'], $user['role']);
			
			redirect('/admin/users?success=user_deleted');
		} catch (\Exception $e) {
			$pdo->rollBack();
			
			// Log failure outcome
			\App\Logger::debug('user_deletion_failed', 'user', $id, 
				"User deletion failed: " . $e->getMessage());
			\App\Logger::error('user_deletion_failed', 'user', $id, 
				"User deletion failed: " . $e->getMessage());
			
			redirect('/admin/users?error=delete_failed');
		}
	}
	
	public function removeAllJudges(): void {
		require_organizer();
		
		// Debug log bulk removal attempt
		\App\Logger::debug('bulk_judge_removal_attempt', 'judge', null, 
			"Attempting to remove all judges");
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Get all judge users
			$judgeUsers = $pdo->query('SELECT * FROM users WHERE role = "judge"')->fetchAll(\PDO::FETCH_ASSOC);
			$judgeCount = count($judgeUsers);
			
			\App\Logger::debug('bulk_judge_removal_details', 'judge', null, 
				"Found {$judgeCount} judges to remove");
			
			foreach ($judgeUsers as $user) {
				if ($user['judge_id']) {
					// Delete associated image file
					$stmt = $pdo->prepare('SELECT image_path FROM judges WHERE id = ?');
					$stmt->execute([$user['judge_id']]);
					$imagePath = $stmt->fetchColumn();
					if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
						unlink(__DIR__ . '/../../public' . $imagePath);
						\App\Logger::debug('judge_image_deleted', 'judge', $user['judge_id'], 
							"Judge image file deleted: {$imagePath}");
					}
					
					$pdo->prepare('DELETE FROM judge_certifications WHERE judge_id = ?')->execute([$user['judge_id']]);
					$pdo->prepare('DELETE FROM judge_comments WHERE judge_id = ?')->execute([$user['judge_id']]);
					$pdo->prepare('DELETE FROM scores WHERE judge_id = ?')->execute([$user['judge_id']]);
					$pdo->prepare('DELETE FROM subcategory_judges WHERE judge_id = ?')->execute([$user['judge_id']]);
					$pdo->prepare('DELETE FROM category_judges WHERE judge_id = ?')->execute([$user['judge_id']]);
					$pdo->prepare('DELETE FROM judges WHERE id = ?')->execute([$user['judge_id']]);
				}
			}
			
			// Delete all judge users
			$pdo->prepare('DELETE FROM users WHERE role = "judge"')->execute();
			
			$pdo->commit();
			
			// Log successful outcome
			\App\Logger::debug('bulk_judge_removal_success', 'judge', null, 
				"Bulk judge removal completed successfully: removed {$judgeCount} judges");
			\App\Logger::logBulkOperation('judge_removal', 'judge', null, 
				"Removed all {$judgeCount} judges and associated data");
			
			redirect('/admin/users?success=all_judges_removed');
		} catch (\Exception $e) {
			$pdo->rollBack();
			
			// Log failure outcome
			\App\Logger::debug('bulk_judge_removal_failed', 'judge', null, 
				"Bulk judge removal failed: " . $e->getMessage());
			\App\Logger::error('bulk_judge_removal_failed', 'judge', null, 
				"Bulk judge removal failed: " . $e->getMessage());
			
			redirect('/admin/users?error=remove_failed');
		}
	}
	
	public function removeAllContestants(): void {
		require_organizer();
		
		// Debug log bulk removal attempt
		\App\Logger::debug('bulk_contestant_removal_attempt', 'contestant', null, 
			"Attempting to remove all contestants");
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Get all contestant users
			$contestantUsers = $pdo->query('SELECT * FROM users WHERE role = "contestant"')->fetchAll(\PDO::FETCH_ASSOC);
			$contestantCount = count($contestantUsers);
			
			\App\Logger::debug('bulk_contestant_removal_details', 'contestant', null, 
				"Found {$contestantCount} contestants to remove");
			
			foreach ($contestantUsers as $user) {
				if ($user['contestant_id']) {
					// Delete associated image file
					$stmt = $pdo->prepare('SELECT image_path FROM contestants WHERE id = ?');
					$stmt->execute([$user['contestant_id']]);
					$imagePath = $stmt->fetchColumn();
					if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
						unlink(__DIR__ . '/../../public' . $imagePath);
						\App\Logger::debug('contestant_image_deleted', 'contestant', $user['contestant_id'], 
							"Contestant image file deleted: {$imagePath}");
					}
					
					$pdo->prepare('DELETE FROM judge_comments WHERE contestant_id = ?')->execute([$user['contestant_id']]);
					$pdo->prepare('DELETE FROM scores WHERE contestant_id = ?')->execute([$user['contestant_id']]);
					$pdo->prepare('DELETE FROM subcategory_contestants WHERE contestant_id = ?')->execute([$user['contestant_id']]);
					$pdo->prepare('DELETE FROM category_contestants WHERE contestant_id = ?')->execute([$user['contestant_id']]);
					$pdo->prepare('DELETE FROM contestants WHERE id = ?')->execute([$user['contestant_id']]);
				}
			}
			
			// Delete all contestant users
			$pdo->prepare('DELETE FROM users WHERE role = "contestant"')->execute();
			
			$pdo->commit();
			
			// Log successful outcome
			\App\Logger::debug('bulk_contestant_removal_success', 'contestant', null, 
				"Bulk contestant removal completed successfully: removed {$contestantCount} contestants");
			\App\Logger::logBulkOperation('contestant_removal', 'contestant', null, 
				"Removed all {$contestantCount} contestants and associated data");
			
			redirect('/admin/users?success=all_contestants_removed');
		} catch (\Exception $e) {
			$pdo->rollBack();
			
			// Log failure outcome
			\App\Logger::debug('bulk_contestant_removal_failed', 'contestant', null, 
				"Bulk contestant removal failed: " . $e->getMessage());
			\App\Logger::error('bulk_contestant_removal_failed', 'contestant', null, 
				"Bulk contestant removal failed: " . $e->getMessage());
			
			redirect('/admin/users?error=remove_failed');
		}
	}
	
	public function removeAllEmcees(): void {
		require_organizer();
		
		// Debug log bulk removal attempt
		\App\Logger::debug('bulk_emcee_removal_attempt', 'emcee', null, 
			"Attempting to remove all emcees");
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Get count before deletion for logging
			$emceeCount = $pdo->query('SELECT COUNT(*) FROM users WHERE role = "emcee"')->fetchColumn();
			
			\App\Logger::debug('bulk_emcee_removal_details', 'emcee', null, 
				"Found {$emceeCount} emcees to remove");
			
			// Delete all emcee users
			$pdo->prepare('DELETE FROM users WHERE role = "emcee"')->execute();
			
			$pdo->commit();
			
			// Log successful outcome
			\App\Logger::debug('bulk_emcee_removal_success', 'emcee', null, 
				"Bulk emcee removal completed successfully: removed {$emceeCount} emcees");
			\App\Logger::logBulkOperation('emcee_removal', 'emcee', null, 
				"Removed all {$emceeCount} emcees");
			
			redirect('/admin/users?success=all_emcees_removed');
		} catch (\Exception $e) {
			$pdo->rollBack();
			
			// Log failure outcome
			\App\Logger::debug('bulk_emcee_removal_failed', 'emcee', null, 
				"Bulk emcee removal failed: " . $e->getMessage());
			\App\Logger::error('bulk_emcee_removal_failed', 'emcee', null, 
				"Bulk emcee removal failed: " . $e->getMessage());
			
			redirect('/admin/users?error=remove_failed');
		}
	}
	
	public function forceRefresh(): void {
		require_organizer();
		
		// Debug log refresh attempt
		\App\Logger::debug('table_refresh_attempt', 'system', null, 
			"Attempting to force refresh user tables");
		
		try {
			// Log successful outcome
			\App\Logger::debug('table_refresh_success', 'system', null, 
				"Table refresh completed successfully");
			\App\Logger::logAdminAction('table_refresh', 'system', null, 
				"User tables refreshed");
			
			redirect('/admin/users?success=tables_refreshed');
		} catch (\Exception $e) {
			// Log failure outcome
			\App\Logger::debug('table_refresh_failed', 'system', null, 
				"Table refresh failed: " . $e->getMessage());
			\App\Logger::error('table_refresh_failed', 'system', null, 
				"Table refresh failed: " . $e->getMessage());
			
			redirect('/admin/users?error=refresh_failed');
		}
	}
}

class DatabaseBrowserController {
	public function index(): void {
		require_organizer();
		
		\App\Logger::debug('db_browser_access', 'database', null, 
			"Admin accessed database browser");
		
		// Get all tables
		$tables = DB::pdo()->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetchAll(\PDO::FETCH_COLUMN);
		
		// Get table info
		$tableInfo = [];
		foreach ($tables as $table) {
			$count = DB::pdo()->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
			$tableInfo[$table] = [
				'name' => $table,
				'count' => $count,
				'columns' => $this->getTableColumns($table)
			];
		}
		
		view('admin/database_browser', compact('tableInfo'));
	}
	
	public function table(array $params): void {
		require_organizer();
		
		$tableName = param('table', $params);
		$page = (int)(param('page', $params) ?: 1);
		$perPage = 50;
		$offset = ($page - 1) * $perPage;
		
		\App\Logger::debug('db_browser_table_access', 'database', $tableName, 
			"Admin accessed table: {$tableName}, page: {$page}");
		
		// Validate table name (security)
		$validTables = DB::pdo()->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(\PDO::FETCH_COLUMN);
		if (!in_array($tableName, $validTables)) {
			redirect('/admin/database?error=invalid_table');
			return;
		}
		
		// Get table structure
		$columns = $this->getTableColumns($tableName);
		
		// Get total count
		$totalCount = DB::pdo()->query("SELECT COUNT(*) FROM `{$tableName}`")->fetchColumn();
		$totalPages = ceil($totalCount / $perPage);
		
		// Get data with pagination
		$data = DB::pdo()->query("SELECT * FROM `{$tableName}` LIMIT {$perPage} OFFSET {$offset}")->fetchAll(\PDO::FETCH_ASSOC);
		
		view('admin/database_table', compact('tableName', 'columns', 'data', 'page', 'totalPages', 'totalCount', 'perPage'));
	}
	
	public function query(): void {
		require_organizer();
		
		$sql = post('sql');
		$action = post('action');
		
		\App\Logger::debug('db_browser_query_execution', 'database', null, 
			"Admin executed SQL query: " . substr($sql, 0, 100) . "...");
		
		if (empty($sql)) {
			redirect('/admin/database?error=empty_query');
			return;
		}
		
		// Security: Only allow SELECT statements
		$sqlTrimmed = trim(strtoupper($sql));
		if (!str_starts_with($sqlTrimmed, 'SELECT')) {
			\App\Logger::warn('db_browser_security_block', 'database', null, 
				"Blocked non-SELECT query: " . substr($sql, 0, 50));
			redirect('/admin/database?error=invalid_query_type');
			return;
		}
		
		try {
			$stmt = DB::pdo()->prepare($sql);
			$stmt->execute();
			
			if ($action === 'count') {
				$result = $stmt->fetchColumn();
				$_SESSION['query_result'] = ['type' => 'count', 'result' => $result];
			} else {
				$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				$_SESSION['query_result'] = ['type' => 'data', 'result' => $result];
			}
			
			\App\Logger::debug('db_browser_query_success', 'database', null, 
				"Query executed successfully, returned " . count($result) . " rows");
			
			redirect('/admin/database?success=query_executed');
		} catch (\Exception $e) {
			\App\Logger::error('db_browser_query_error', 'database', null, 
				"Query execution failed: " . $e->getMessage());
			redirect('/admin/database?error=query_failed&message=' . urlencode($e->getMessage()));
		}
	}
	
	private function getTableColumns(string $tableName): array {
		$columns = DB::pdo()->query("PRAGMA table_info(`{$tableName}`)")->fetchAll(\PDO::FETCH_ASSOC);
		return $columns;
	}
}

class AdminController {
	public function index(): void {
		require_organizer();
		
		// Get quick stats
		$stats = [
			'total_contests' => DB::pdo()->query('SELECT COUNT(*) FROM contests')->fetchColumn(),
			'total_categories' => DB::pdo()->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
			'total_subcategories' => DB::pdo()->query('SELECT COUNT(*) FROM subcategories')->fetchColumn(),
			'total_contestants' => DB::pdo()->query('SELECT COUNT(*) FROM contestants')->fetchColumn(),
			'total_judges' => DB::pdo()->query('SELECT COUNT(*) FROM judges')->fetchColumn(),
			'total_users' => DB::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
		];
		
		// Get recent activity
		$recentLogs = DB::pdo()->query('SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10')->fetchAll(\PDO::FETCH_ASSOC);
		
		view('admin/index', compact('stats', 'recentLogs'));
	}
	
	public function activeUsersApi(): void {
		require_organizer();
		
		// Get users who are currently logged in (have last_login within last 30 minutes)
		$activeUsers = DB::pdo()->query('
			SELECT DISTINCT u.name, u.email, u.role, u.preferred_name, 
			       u.last_login,
			       al.ip_address
			FROM users u 
			LEFT JOIN activity_logs al ON u.name = al.user_name 
			WHERE u.password_hash IS NOT NULL 
			AND u.last_login IS NOT NULL 
			AND u.last_login > datetime("now", "-30 minutes")
			ORDER BY u.last_login DESC, u.name
		')->fetchAll(\PDO::FETCH_ASSOC);
		
		// Set JSON response headers
		header('Content-Type: application/json');
		header('Cache-Control: no-cache, no-store, must-revalidate');
		header('Pragma: no-cache');
		header('Expires: 0');
		
		echo json_encode(['users' => $activeUsers]);
	}
	
	public function judges(): void {
		require_organizer();
		$rows = DB::pdo()->query('SELECT j.*, u.preferred_name FROM judges j LEFT JOIN users u ON j.id = u.judge_id ORDER BY j.name')->fetchAll(\PDO::FETCH_ASSOC);
		view('admin/judges', compact('rows'));
	}
	
	public function createJudge(): void {
		require_organizer();
		$name = post('name');
		$email = post('email') ?: null;
		$gender = post('gender') ?: null;
		$isHeadJudge = post('is_head_judge') ? 1 : 0;
		
		// Debug log creation attempt
		\App\Logger::debug('judge_creation_attempt', 'judge', null, 
			"Attempting to create judge: name={$name}, email={$email}, gender={$gender}, is_head_judge={$isHeadJudge}");
		
		try {
			$judgeId = uuid();
			$stmt = DB::pdo()->prepare('INSERT INTO judges (id, name, email, gender, is_head_judge) VALUES (?, ?, ?, ?, ?)');
			$stmt->execute([$judgeId, $name, $email, $gender, $isHeadJudge]);
			
			// Log successful outcome
			\App\Logger::debug('judge_creation_success', 'judge', $judgeId, 
				"Judge created successfully: judge_id={$judgeId}, name={$name}, email={$email}, is_head_judge={$isHeadJudge}");
			\App\Logger::logAdminAction('judge_created', 'judge', $judgeId, 
				"Judge created: {$name}" . ($isHeadJudge ? " (Head Judge)" : ""));
			
			redirect('/admin/judges?success=judge_created');
		} catch (\Exception $e) {
			// Log failure outcome
			\App\Logger::debug('judge_creation_failed', 'judge', null, 
				"Judge creation failed: " . $e->getMessage());
			\App\Logger::error('judge_creation_failed', 'judge', null, 
				"Judge creation failed: " . $e->getMessage());
			
			redirect('/admin/judges?error=creation_failed');
		}
	}
	
	public function updateJudge(array $params): void {
		require_organizer();
		$id = param('id', $params);
		$name = post('name');
		$email = post('email') ?: null;
		$gender = post('gender') ?: null;
		$isHeadJudge = post('is_head_judge') ? 1 : 0;
		
		// Debug log update attempt
		\App\Logger::debug('judge_update_attempt', 'judge', $id, 
			"Attempting to update judge: judge_id={$id}, name={$name}, email={$email}, is_head_judge={$isHeadJudge}");
		
		try {
			$stmt = DB::pdo()->prepare('UPDATE judges SET name = ?, email = ?, gender = ?, is_head_judge = ? WHERE id = ?');
			$stmt->execute([$name, $email, $gender, $isHeadJudge, $id]);
			
			// Log successful outcome
			\App\Logger::debug('judge_update_success', 'judge', $id, 
				"Judge updated successfully: judge_id={$id}, name={$name}, email={$email}, is_head_judge={$isHeadJudge}");
			\App\Logger::logAdminAction('judge_updated', 'judge', $id, 
				"Judge updated: {$name}" . ($isHeadJudge ? " (Head Judge)" : ""));
			
			redirect('/admin/judges?success=judge_updated');
		} catch (\Exception $e) {
			// Log failure outcome
			\App\Logger::debug('judge_update_failed', 'judge', $id, 
				"Judge update failed: " . $e->getMessage());
			\App\Logger::error('judge_update_failed', 'judge', $id, 
				"Judge update failed: " . $e->getMessage());
			
			redirect('/admin/judges?error=update_failed');
		}
	}
	
	public function deleteJudge(): void {
		require_organizer();
		$id = post('judge_id');
		
		// Debug log deletion attempt
		\App\Logger::debug('judge_deletion_attempt', 'judge', $id, 
			"Attempting to delete judge: judge_id={$id}");
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Get judge info for logging
			$stmt = $pdo->prepare('SELECT * FROM judges WHERE id = ?');
			$stmt->execute([$id]);
			$judge = $stmt->fetch(\PDO::FETCH_ASSOC);
			
			if (!$judge) {
				\App\Logger::debug('judge_deletion_failed', 'judge', $id, 
					"Judge deletion failed: judge not found");
				redirect('/admin/judges?error=judge_not_found');
				return;
			}
			
			\App\Logger::debug('judge_deletion_details', 'judge', $id, 
				"Judge deletion details: name={$judge['name']}, email={$judge['email']}");
			
			// Delete associated image file
			$stmt = $pdo->prepare('SELECT image_path FROM judges WHERE id = ?');
			$stmt->execute([$id]);
			$imagePath = $stmt->fetchColumn();
			if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
				unlink(__DIR__ . '/../../public' . $imagePath);
				\App\Logger::debug('judge_image_deleted', 'judge', $id, 
					"Judge image file deleted: {$imagePath}");
			}
			
			// Delete all related data
			$pdo->prepare('DELETE FROM judge_certifications WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM judge_comments WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM scores WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM subcategory_judges WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM category_judges WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM judges WHERE id = ?')->execute([$id]);
			
			$pdo->commit();
			
			// Log successful outcome
			\App\Logger::debug('judge_deletion_success', 'judge', $id, 
				"Judge deletion completed successfully: judge_id={$id}, name={$judge['name']}");
			\App\Logger::logUserDeletion($id, $judge['name'], 'judge');
			
			redirect('/admin/judges?success=judge_deleted');
		} catch (\Exception $e) {
			$pdo->rollBack();
			
			// Log failure outcome
			\App\Logger::debug('judge_deletion_failed', 'judge', $id, 
				"Judge deletion failed: " . $e->getMessage());
			\App\Logger::error('judge_deletion_failed', 'judge', $id, 
				"Judge deletion failed: " . $e->getMessage());
			
			redirect('/admin/judges?error=delete_failed');
		}
	}
	
	public function contestants(): void {
		require_organizer();
		$contestants = DB::pdo()->query('SELECT c.*, u.preferred_name FROM contestants c LEFT JOIN users u ON c.id = u.contestant_id ORDER BY c.contestant_number IS NULL, c.contestant_number, c.name')->fetchAll(\PDO::FETCH_ASSOC);
		view('admin/contestants', compact('contestants'));
	}
	
	public function createContestant(): void {
		require_organizer();
		$name = post('name');
		$email = post('email') ?: null;
		$gender = post('gender') ?: null;
		$contestantNumber = post('contestant_number') ?: null;
		
		// Debug log creation attempt
		\App\Logger::debug('contestant_creation_attempt', 'contestant', null, 
			"Attempting to create contestant: name={$name}, email={$email}, gender={$gender}");
		
		try {
			$contestantId = uuid();
			$stmt = DB::pdo()->prepare('INSERT INTO contestants (id, name, email, gender, contestant_number) VALUES (?, ?, ?, ?, ?)');
			$stmt->execute([$contestantId, $name, $email, $gender, $contestantNumber]);
			
			// Log successful outcome
			\App\Logger::debug('contestant_creation_success', 'contestant', $contestantId, 
				"Contestant created successfully: contestant_id={$contestantId}, name={$name}, email={$email}");
			\App\Logger::logAdminAction('contestant_created', 'contestant', $contestantId, 
				"Contestant created: {$name}");
			
			redirect('/admin/contestants?success=contestant_created');
		} catch (\Exception $e) {
			// Log failure outcome
			\App\Logger::debug('contestant_creation_failed', 'contestant', null, 
				"Contestant creation failed: " . $e->getMessage());
			\App\Logger::error('contestant_creation_failed', 'contestant', null, 
				"Contestant creation failed: " . $e->getMessage());
			
			redirect('/admin/contestants?error=creation_failed');
		}
	}
	
	public function deleteContestant(): void {
		require_organizer();
		$id = post('contestant_id');
		
		// Debug log deletion attempt
		\App\Logger::debug('contestant_deletion_attempt', 'contestant', $id, 
			"Attempting to delete contestant: contestant_id={$id}");
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Get contestant info for logging
			$stmt = $pdo->prepare('SELECT * FROM contestants WHERE id = ?');
			$stmt->execute([$id]);
			$contestant = $stmt->fetch(\PDO::FETCH_ASSOC);
			
			if (!$contestant) {
				\App\Logger::debug('contestant_deletion_failed', 'contestant', $id, 
					"Contestant deletion failed: contestant not found");
				redirect('/admin/contestants?error=contestant_not_found');
				return;
			}
			
			\App\Logger::debug('contestant_deletion_details', 'contestant', $id, 
				"Contestant deletion details: name={$contestant['name']}, email={$contestant['email']}");
			
			// Delete associated image file
			$stmt = $pdo->prepare('SELECT image_path FROM contestants WHERE id = ?');
			$stmt->execute([$id]);
			$imagePath = $stmt->fetchColumn();
			if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
				unlink(__DIR__ . '/../../public' . $imagePath);
				\App\Logger::debug('contestant_image_deleted', 'contestant', $id, 
					"Contestant image file deleted: {$imagePath}");
			}
			
			// Delete all related data
			$pdo->prepare('DELETE FROM judge_comments WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM scores WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM subcategory_contestants WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM category_contestants WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM contestants WHERE id = ?')->execute([$id]);
			
			$pdo->commit();
			
			// Log successful outcome
			\App\Logger::debug('contestant_deletion_success', 'contestant', $id, 
				"Contestant deletion completed successfully: contestant_id={$id}, name={$contestant['name']}");
			\App\Logger::logUserDeletion($id, $contestant['name'], 'contestant');
			
			redirect('/admin/contestants?success=contestant_deleted');
		} catch (\Exception $e) {
			$pdo->rollBack();
			
			// Log failure outcome
			\App\Logger::debug('contestant_deletion_failed', 'contestant', $id, 
				"Contestant deletion failed: " . $e->getMessage());
			\App\Logger::error('contestant_deletion_failed', 'contestant', $id, 
				"Contestant deletion failed: " . $e->getMessage());
			
			redirect('/admin/contestants?error=delete_failed');
		}
	}
	
	public function organizers(): void {
		require_organizer();
		$organizers = DB::pdo()->query('SELECT * FROM users WHERE role = "organizer" ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		view('admin/organizers', compact('organizers'));
	}
	
	public function createOrganizer(): void {
		require_organizer();
		$name = post('name');
		$email = post('email') ?: null;
		$password = post('password');
		$preferredName = post('preferred_name') ?: $name;
		$gender = post('gender') ?: null;
		
		// Debug log creation attempt
		\App\Logger::debug('organizer_creation_attempt', 'organizer', null, 
			"Attempting to create organizer: name={$name}, email={$email}, gender={$gender}");
		
		try {
			$organizerId = uuid();
			$passwordHash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
			
			$stmt = DB::pdo()->prepare('INSERT INTO users (id, name, email, password_hash, role, preferred_name, gender) VALUES (?, ?, ?, ?, ?, ?, ?)');
			$stmt->execute([$organizerId, $name, $email, $passwordHash, 'organizer', $preferredName, $gender]);
			
			// Log successful outcome
			\App\Logger::debug('organizer_creation_success', 'organizer', $organizerId, 
				"Organizer created successfully: organizer_id={$organizerId}, name={$name}, email={$email}");
			\App\Logger::logAdminAction('organizer_created', 'organizer', $organizerId, 
				"Organizer created: {$name}");
			
			redirect('/admin/organizers?success=organizer_created');
		} catch (\Exception $e) {
			// Log failure outcome
			\App\Logger::debug('organizer_creation_failed', 'organizer', null, 
				"Organizer creation failed: " . $e->getMessage());
			\App\Logger::error('organizer_creation_failed', 'organizer', null, 
				"Organizer creation failed: " . $e->getMessage());
			
			redirect('/admin/organizers?error=creation_failed');
		}
	}
	
	public function deleteOrganizer(): void {
		require_organizer();
		$id = post('organizer_id');
		
		// Debug log deletion attempt
		\App\Logger::debug('organizer_deletion_attempt', 'organizer', $id, 
			"Attempting to delete organizer: organizer_id={$id}");
		
		// Don't allow deleting yourself
		if ($id === ($_SESSION['user']['id'] ?? '')) {
			\App\Logger::debug('organizer_deletion_failed', 'organizer', $id, 
				"Organizer deletion failed: cannot delete self");
			redirect('/admin/organizers?error=cannot_delete_self');
			return;
		}
		
		try {
			// Get organizer info for logging
			$stmt = DB::pdo()->prepare('SELECT * FROM users WHERE id = ? AND role = "organizer"');
			$stmt->execute([$id]);
			$organizer = $stmt->fetch(\PDO::FETCH_ASSOC);
			
			if (!$organizer) {
				\App\Logger::debug('organizer_deletion_failed', 'organizer', $id, 
					"Organizer deletion failed: organizer not found");
				redirect('/admin/organizers?error=organizer_not_found');
				return;
			}
			
			\App\Logger::debug('organizer_deletion_details', 'organizer', $id, 
				"Organizer deletion details: name={$organizer['name']}, email={$organizer['email']}");
			
			DB::pdo()->prepare('DELETE FROM users WHERE id = ? AND role = "organizer"')->execute([$id]);
			
			// Log successful outcome
			\App\Logger::debug('organizer_deletion_success', 'organizer', $id, 
				"Organizer deletion completed successfully: organizer_id={$id}, name={$organizer['name']}");
			\App\Logger::logUserDeletion($id, $organizer['name'], 'organizer');
			
			redirect('/admin/organizers?success=organizer_deleted');
		} catch (\Exception $e) {
			// Log failure outcome
			\App\Logger::debug('organizer_deletion_failed', 'organizer', $id, 
				"Organizer deletion failed: " . $e->getMessage());
			\App\Logger::error('organizer_deletion_failed', 'organizer', $id, 
				"Organizer deletion failed: " . $e->getMessage());
			
			redirect('/admin/organizers?error=delete_failed');
		}
	}
	
	public function settings(): void {
		require_organizer();
		
		// Refresh Logger to load current log level from database
		\App\Logger::refreshLevel();
		
		// Get current settings
		$settings = [];
		$stmt = DB::pdo()->query('SELECT setting_key, setting_value FROM system_settings');
		while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			$settings[$row['setting_key']] = $row['setting_value'];
		}
		
		// Get the actual Logger level (what's currently active)
		$currentLoggerLevel = \App\Logger::getLevel();
		
		view('admin/settings', compact('settings', 'currentLoggerLevel'));
	}
	
	public function testLogLevel(): void {
		require_organizer();
		
		echo '<pre>=== Log Level Test ===</pre>';
		
		// Test all log levels
		$levels = ['debug', 'info', 'warn', 'error'];
		foreach ($levels as $level) {
			echo '<pre>Testing ' . $level . ' level:</pre>';
			\App\Logger::setLevel($level);
			echo '<pre>  Current level: ' . \App\Logger::getLevel() . '</pre>';
			echo '<pre>  Should log debug: ' . (\App\Logger::shouldLog('debug') ? 'YES' : 'NO') . '</pre>';
			echo '<pre>  Should log info: ' . (\App\Logger::shouldLog('info') ? 'YES' : 'NO') . '</pre>';
			echo '<pre>  Should log warn: ' . (\App\Logger::shouldLog('warn') ? 'YES' : 'NO') . '</pre>';
			echo '<pre>  Should log error: ' . (\App\Logger::shouldLog('error') ? 'YES' : 'NO') . '</pre>';
			echo '<pre></pre>';
		}
		
		echo '<pre>=== Test Complete ===</pre>';
		exit;
	}
	
	public function testLogging(): void {
		require_organizer();
		
		echo '<pre>=== Logging Test ===</pre>';
		
		// Get current Logger level
		$currentLevel = \App\Logger::getLevel();
		echo '<pre>Current Logger Level: ' . $currentLevel . '</pre>';
		
		// Get database level for comparison
		$stmt = DB::pdo()->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
		$stmt->execute(['log_level']);
		$dbLevel = $stmt->fetchColumn();
		echo '<pre>Database Level: ' . ($dbLevel ?: 'NOT SET') . '</pre>';
		
		// Force refresh and check again
		echo '<pre>Forcing Logger refresh...</pre>';
		\App\Logger::refreshLevel();
		$refreshedLevel = \App\Logger::getLevel();
		echo '<pre>After refresh: ' . $refreshedLevel . '</pre>';
		
		// Test each log level
		echo '<pre>Testing debug log...</pre>';
		\App\Logger::debug('test_debug', 'system', null, 'This is a debug message');
		
		echo '<pre>Testing info log...</pre>';
		\App\Logger::info('test_info', 'system', null, 'This is an info message');
		
		echo '<pre>Testing warn log...</pre>';
		\App\Logger::warn('test_warn', 'system', null, 'This is a warning message');
		
		echo '<pre>Testing error log...</pre>';
		\App\Logger::error('test_error', 'system', null, 'This is an error message');
		
		echo '<pre>Testing custom log methods...</pre>';
		\App\Logger::logAdminAction('test_action', 'system', null, 'Testing admin action logging');
		\App\Logger::logDataAccess('test_resource', 'test_id', 'test_action');
		
		echo '<pre>=== Test Complete ===</pre>';
		echo '<pre>Check the Activity Logs page to see which messages were logged based on the current level.</pre>';
		exit;
	}
	
	public function testEmailConnection(): void {
		require_organizer();
		
		// Force refresh Logger level
		\App\Logger::refreshLevel();
		
		// Log that the method was called
		\App\Logger::info('email_test_method_called', 'email', null, "Email test method called by user");
		
		// Also log to error_log for immediate debugging
		error_log('EMAIL TEST: Method called at ' . date('Y-m-d H:i:s'));
		
		// Get current SMTP settings from database
		$settings = [];
		try {
			$stmt = DB::pdo()->query('SELECT setting_key, setting_value FROM system_settings');
			while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
				$settings[$row['setting_key']] = $row['setting_value'];
			}
			\App\Logger::debug('email_test_settings_loaded', 'email', null, "Loaded " . count($settings) . " settings from database");
		} catch (\Exception $e) {
			\App\Logger::error('email_test_settings_error', 'email', null, "Failed to load settings: " . $e->getMessage());
		}
		
		// Test email connection
		$testEmail = post('test_email') ?: ($settings['smtp_from_email'] ?? 'test@example.com');
		$testSubject = 'Event Manager - SMTP Connection Test';
		$testMessage = '<h2>SMTP Connection Test</h2><p>This is a test email to verify your SMTP configuration.</p><p><strong>Test Details:</strong></p><ul><li>Time: ' . date('Y-m-d H:i:s') . '</li><li>From: ' . ($settings['smtp_from_email'] ?? 'not configured') . '</li><li>SMTP Host: ' . ($settings['smtp_host'] ?? 'not configured') . '</li><li>Port: ' . ($settings['smtp_port'] ?? 'not configured') . '</li><li>Security: ' . ($settings['smtp_secure'] ?? 'not configured') . '</li></ul><p>If you received this email, your SMTP configuration is working correctly!</p>';
		
		\App\Logger::debug('email_test_attempt', 'email', null, "Testing SMTP connection to: {$testEmail}");
		
		// Check if PHPMailer is available
		$autoload = __DIR__ . '/../../vendor/autoload.php';
		if (!file_exists($autoload)) {
			\App\Logger::error('email_test_phpmailer_missing', 'email', null, 'PHPMailer not installed (vendor/autoload.php missing)');
			error_log('EMAIL TEST: PHPMailer not found, redirecting with error');
			redirect('/admin/settings?error=email_test_exception&details=' . urlencode('PHPMailer not installed. Please run: composer install'));
			return;
		}
		
		error_log('EMAIL TEST: PHPMailer found, proceeding with test');
		
		try {
			$result = \App\Mailer::sendHtml($testEmail, $testSubject, $testMessage);
			
			if ($result) {
				\App\Logger::info('email_test_success', 'email', null, "SMTP test email sent successfully to: {$testEmail}");
				redirect('/admin/settings?success=email_test_success&test_email=' . urlencode($testEmail));
			} else {
				\App\Logger::error('email_test_failed', 'email', null, "SMTP test email failed to send to: {$testEmail}");
				redirect('/admin/settings?error=email_test_failed');
			}
		} catch (\Throwable $e) {
			\App\Logger::error('email_test_exception', 'email', null, "SMTP test exception: " . $e->getMessage());
			redirect('/admin/settings?error=email_test_exception&details=' . urlencode($e->getMessage()));
		}
	}
	
	public function updateSettings(): void {
		require_organizer();
		
		$sessionTimeout = (int)post('session_timeout');
		$logLevel = post('log_level');
		// PHPMailer / SMTP
		$smtpSettings = [
			'smtp_enabled' => (string)post('smtp_enabled'),
			'smtp_from_email' => (string)post('smtp_from_email'),
			'smtp_from_name' => (string)post('smtp_from_name'),
			'smtp_host' => (string)post('smtp_host'),
			'smtp_port' => (string)post('smtp_port'),
			'smtp_secure' => (string)post('smtp_secure'),
			'smtp_auth' => (string)post('smtp_auth'),
			'smtp_username' => (string)post('smtp_username'),
			'smtp_password' => (string)post('smtp_password'),
		];
		
		// Debug log the settings change attempt
		\App\Logger::debug('settings_update_attempt', 'system_settings', null, 
			"Attempting to update settings: session_timeout={$sessionTimeout}, log_level={$logLevel}");
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Update session timeout
			$stmt = $pdo->prepare('INSERT OR REPLACE INTO system_settings (setting_key, setting_value) VALUES (?, ?)');
			$stmt->execute(['session_timeout', $sessionTimeout]);
			\App\Logger::debug('settings_session_timeout_updated', 'system_settings', 'session_timeout', 
				"Session timeout updated to {$sessionTimeout} seconds");
			
			// Update log level
			$stmt = $pdo->prepare('INSERT OR REPLACE INTO system_settings (setting_key, setting_value) VALUES (?, ?)');
			$stmt->execute(['log_level', $logLevel]);
			\App\Logger::debug('settings_log_level_updated', 'system_settings', 'log_level', 
				"Log level updated to {$logLevel}");
			
			// Update SMTP settings
			foreach ($smtpSettings as $k => $v) {
				$stmt = $pdo->prepare('INSERT OR REPLACE INTO system_settings (setting_key, setting_value) VALUES (?, ?)');
				$stmt->execute([$k, $v]);
			}

			$pdo->commit();
			
			// Apply the new log level immediately
			\App\Logger::setLevel($logLevel);
			\App\Logger::debug('settings_log_level_applied', 'system_settings', 'log_level', 
				"Log level {$logLevel} applied to Logger instance");
			
			\App\Logger::logAdminAction('settings_updated', 'system_settings', null, 
				"System settings updated: timeout={$sessionTimeout}s, log_level={$logLevel}, smtp_updated=1");
			
			redirect('/admin/settings?success=settings_updated');
		} catch (\Exception $e) {
			$pdo->rollBack();
			\App\Logger::error('settings_update_failed', 'system_settings', null, 
				"Failed to update settings: " . $e->getMessage());
			redirect('/admin/settings?error=update_failed');
		}
	}
	
	public function logs(): void {
		require_organizer();
		
		// Refresh Logger to ensure we have the latest log level
		\App\Logger::refreshLevel();
		
		// Get current log level from Logger (site-wide setting)
		$currentLogLevel = \App\Logger::getLevel();
		
		// Get filter parameters
		$logLevel = $_GET['log_level'] ?? ''; // Don't default to Logger level - allow manual filtering
		$userRole = $_GET['user_role'] ?? '';
		$action = $_GET['action'] ?? '';
		$dateFrom = $_GET['date_from'] ?? '';
		$dateTo = $_GET['date_to'] ?? '';
		$page = (int)($_GET['page'] ?? 1);
		$perPage = 50;
		$offset = ($page - 1) * $perPage;
		
		// Build query
		$where = [];
		$params = [];
		
		if ($logLevel) {
			$where[] = 'log_level = ?';
			$params[] = $logLevel;
		}
		if ($userRole) {
			$where[] = 'user_role = ?';
			$params[] = $userRole;
		}
		if ($action) {
			$where[] = 'action = ?';
			$params[] = $action;
		}
		if ($dateFrom) {
			$where[] = 'created_at >= ?';
			$params[] = $dateFrom;
		}
		if ($dateTo) {
			$where[] = 'created_at <= ?';
			$params[] = $dateTo;
		}
		
		$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
		
		// Get total count
		$countSql = "SELECT COUNT(*) FROM activity_logs $whereClause";
		$stmt = DB::pdo()->prepare($countSql);
		$stmt->execute($params);
		$totalLogs = $stmt->fetchColumn();
		
		// Get logs
		$sql = "SELECT * FROM activity_logs $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
		$params[] = $perPage;
		$params[] = $offset;
		$stmt = DB::pdo()->prepare($sql);
		$stmt->execute($params);
		$logs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		
		// Calculate total pages
		$totalPages = ceil($totalLogs / $perPage);
		
		// Get available roles and actions for filter dropdowns
		$availableRoles = ['organizer', 'judge', 'emcee', 'contestant'];
		$stmt = DB::pdo()->query('SELECT DISTINCT action FROM activity_logs ORDER BY action');
		$availableActions = $stmt->fetchAll(\PDO::FETCH_COLUMN);
		
		view('admin/logs', compact('logs', 'totalLogs', 'totalPages', 'page', 'perPage', 'currentLogLevel', 'logLevel', 'userRole', 'action', 'dateFrom', 'dateTo', 'availableRoles', 'availableActions'));
	}
	
	public function logFiles(): void {
		require_organizer();
		
		// Debug log file retrieval
		\App\Logger::debug('log_files_retrieval', 'log_files', null, 
			"Retrieving log files for admin view");
		
		$logFiles = \App\Logger::getLogFiles();
		$logDirectory = \App\Logger::getLogDirectoryPublic();
		
		\App\Logger::debug('log_files_found', 'log_files', null, 
			"Found " . count($logFiles) . " log files in directory: " . $logDirectory);
		
		// Get file info for each log file
		$fileInfo = [];
		foreach ($logFiles as $file) {
			$fileInfo[] = [
				'filename' => basename($file),
				'path' => $file,
				'size' => filesize($file),
				'modified' => filemtime($file),
				'readable' => is_readable($file)
			];
			
			// Debug log each file's status
			\App\Logger::debug('log_file_info', 'log_files', basename($file), 
				"File: " . basename($file) . ", Size: " . filesize($file) . " bytes, Readable: " . (is_readable($file) ? 'Yes' : 'No'));
		}
		
		view('admin/log_files', compact('fileInfo', 'logDirectory'));
	}
	
	public function viewLogFile(array $params): void {
		require_organizer();
		
		$filename = param('filename', $params);
		$lines = (int)(param('lines', $params) ?: 100);
		
		if (empty($filename)) {
			redirect('/admin/log-files?error=no_filename');
			return;
		}
		
		// Security: only allow viewing log files
		if (!preg_match('/^event-manager-\d{4}-\d{2}-\d{2}\.log$/', $filename)) {
			redirect('/admin/log-files?error=invalid_filename');
			return;
		}
		
		$content = \App\Logger::getLogFileContent($filename, $lines);
		$logDirectory = \App\Logger::getLogDirectoryPublic();
		
		view('admin/view_log_file', compact('filename', 'content', 'lines', 'logDirectory'));
	}
	
	public function downloadLogFile(array $params): void {
		require_organizer();
		
		$filename = param('filename', $params);
		
		if (empty($filename)) {
			redirect('/admin/log-files?error=no_filename');
			return;
		}
		
		// Security: only allow downloading log files
		if (!preg_match('/^event-manager-\d{4}-\d{2}-\d{2}\.log$/', $filename)) {
			redirect('/admin/log-files?error=invalid_filename');
			return;
		}
		
		$logDir = \App\Logger::getLogDirectoryPublic();
		$filePath = $logDir . '/' . $filename;
		
		if (!file_exists($filePath) || !is_readable($filePath)) {
			redirect('/admin/log-files?error=file_not_found');
			return;
		}
		
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Content-Length: ' . filesize($filePath));
		header('Cache-Control: no-cache, must-revalidate');
		
		readfile($filePath);
		exit;
	}
	
	public function cleanupLogFiles(): void {
		require_organizer();
		
		$daysToKeep = (int)(post('days_to_keep') ?: 30);
		$deletedCount = \App\Logger::cleanupOldLogFiles($daysToKeep);
		
		\App\Logger::logAdminAction('log_cleanup', 'system', null, "Cleaned up {$deletedCount} log files older than {$daysToKeep} days");
		
		redirect('/admin/log-files?success=cleanup_complete&deleted=' . $deletedCount);
	}
	
	public function forceLogoutAll(): void {
		require_organizer();
		
		// Debug log logout attempt
		\App\Logger::debug('force_logout_all_attempt', 'system', null, 
			"Attempting to force logout all users");
		
		try {
			// Increment session version and clear last_login for all users
			DB::pdo()->prepare('UPDATE users SET session_version = ?, last_login = NULL')->execute([uuid()]);
			
			// Log successful outcome
			\App\Logger::debug('force_logout_all_success', 'system', null, 
				"Force logout all users completed successfully");
			\App\Logger::logAdminAction('force_logout_all', 'system', '', 'All users logged out');
			
			redirect('/admin/users?success=all_users_logged_out');
		} catch (\Exception $e) {
			// Log failure outcome
			\App\Logger::debug('force_logout_all_failed', 'system', null, 
				"Force logout all users failed: " . $e->getMessage());
			\App\Logger::error('force_logout_all_failed', 'system', null, 
				"Force logout all users failed: " . $e->getMessage());
			
			redirect('/admin/users?error=logout_failed');
		}
	}
	
	public function forceLogoutUser(array $params): void {
		require_organizer();
		$userId = param('id', $params);
		
		// Debug log logout attempt
		\App\Logger::debug('force_logout_user_attempt', 'user', $userId, 
			"Attempting to force logout user: user_id={$userId}");
		
		try {
			// Get user info for logging
			$stmt = DB::pdo()->prepare('SELECT name, email FROM users WHERE id = ?');
			$stmt->execute([$userId]);
			$user = $stmt->fetch(\PDO::FETCH_ASSOC);
			
			if (!$user) {
				\App\Logger::debug('force_logout_user_failed', 'user', $userId, 
					"Force logout user failed: user not found");
				redirect('/admin/users?error=user_not_found');
				return;
			}
			
			\App\Logger::debug('force_logout_user_details', 'user', $userId, 
				"Force logout user details: name={$user['name']}, email={$user['email']}");
			
			// Generate new session version for this user
			$newSessionVersion = uuid();
			DB::pdo()->prepare('UPDATE users SET session_version = ?, last_login = NULL WHERE id = ?')->execute([$newSessionVersion, $userId]);
			
			// Log successful outcome
			\App\Logger::debug('force_logout_user_success', 'user', $userId, 
				"Force logout user completed successfully: user_id={$userId}, name={$user['name']}");
			\App\Logger::logAdminAction('force_logout_user', 'user', $userId, 'User logged out');
			
			redirect('/admin/users?success=user_logged_out');
		} catch (\Exception $e) {
			// Log failure outcome
			\App\Logger::debug('force_logout_user_failed', 'user', $userId, 
				"Force logout user failed: " . $e->getMessage());
			\App\Logger::error('force_logout_user_failed', 'user', $userId, 
				"Force logout user failed: " . $e->getMessage());
			
			redirect('/admin/users?error=logout_failed');
		}
	}
	
	public function printReports(): void {
		require_organizer();
		
		// Get all contestants, judges, and categories for print options
		$contestants = DB::pdo()->query('SELECT * FROM contestants ORDER BY contestant_number IS NULL, contestant_number, name')->fetchAll(\PDO::FETCH_ASSOC);
		$judges = DB::pdo()->query('SELECT * FROM judges ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		$structure = DB::pdo()->query('SELECT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id ORDER BY co.name, c.name')->fetchAll(\PDO::FETCH_ASSOC);
		$usersWithEmail = DB::pdo()->query('SELECT id, preferred_name, name, email FROM users WHERE email IS NOT NULL AND email != "" ORDER BY preferred_name IS NULL, preferred_name, name')->fetchAll(\PDO::FETCH_ASSOC);
		
		view('admin/print_reports', compact('contestants', 'judges', 'structure', 'usersWithEmail'));
	}

	public function emailReport(): void {
		require_organizer();
		
		// Debug log the incoming request
		\App\Logger::debug('email_report_request', 'report', null, 
			"POST data: " . json_encode($_POST));
		
		$reportType = post('report_type'); // contestant|judge|category
		$entityId = post('entity_id');
		$toEmail = trim((string)post('to_email'));
		$userId = trim((string)post('user_id'));
		
		// Resolve recipient
		if ($userId && !$toEmail) {
			$stmt = DB::pdo()->prepare('SELECT email FROM users WHERE id = ?');
			$stmt->execute([$userId]);
			$toEmail = (string)$stmt->fetchColumn();
		}
		
		if (!$toEmail) {
			redirect('/admin/print-reports?error=missing_email');
			return;
		}
		
		// Build report HTML by rendering the same templates used for print
		try {
			$html = '';
			$subject = '';
			if ($reportType === 'contestant') {
				// Reuse PrintController logic
				$pc = new PrintController();
				// Manually duplicate fetch to avoid side-effects of printing headers
				$contestantStmt = DB::pdo()->prepare('SELECT * FROM contestants WHERE id = ?');
				$contestantStmt->execute([$entityId]);
				$contestant = $contestantStmt->fetch(\PDO::FETCH_ASSOC);
				if (!$contestant) { redirect('/admin/print-reports?error=contestant_not_found'); return; }
				$subStmt = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id JOIN subcategory_contestants sc ON s.id = sc.subcategory_id WHERE sc.contestant_id = ? ORDER BY c.name, s.name');
				$subStmt->execute([$entityId]);
				$subcategories = $subStmt->fetchAll(\PDO::FETCH_ASSOC);
				$scoresStmt = DB::pdo()->prepare('SELECT s.*, sc.name as subcategory_name, cr.name as criterion_name, cr.max_score, j.name as judge_name, c.name as category_name, co.name as contest_name FROM scores s JOIN subcategories sc ON s.subcategory_id = sc.id JOIN categories c ON sc.category_id = c.id JOIN contests co ON c.contest_id = co.id JOIN criteria cr ON s.criterion_id = cr.id JOIN judges j ON s.judge_id = j.id WHERE s.contestant_id = ? ORDER BY co.name, c.name, sc.name, cr.name, j.name');
				$scoresStmt->execute([$entityId]);
				$scores = $scoresStmt->fetchAll(\PDO::FETCH_ASSOC);
				$commentsStmt = DB::pdo()->prepare('SELECT jc.*, sc.name as subcategory_name, c.name as category_name, co.name as contest_name, j.name as judge_name FROM judge_comments jc JOIN subcategories sc ON jc.subcategory_id = sc.id JOIN categories c ON sc.category_id = c.id JOIN contests co ON c.contest_id = co.id JOIN judges j ON jc.judge_id = j.id WHERE jc.contestant_id = ? ORDER BY co.name, c.name, sc.name, j.name');
				$commentsStmt->execute([$entityId]);
				$comments = $commentsStmt->fetchAll(\PDO::FETCH_ASSOC);
				$dedStmt = DB::pdo()->prepare('SELECT od.*, sc.name as subcategory_name FROM overall_deductions od JOIN subcategories sc ON od.subcategory_id = sc.id WHERE od.contestant_id = ? ORDER BY sc.name');
				$dedStmt->execute([$entityId]);
				$deductions = $dedStmt->fetchAll(\PDO::FETCH_ASSOC);
				
				$html = \App\render_to_string('print/contestant', compact('contestant','subcategories','scores','comments','deductions'));
				$subject = 'Contestant Report: ' . ($contestant['name'] ?? '');
			} elseif ($reportType === 'judge') {
				$judgeStmt = DB::pdo()->prepare('SELECT * FROM judges WHERE id = ?');
				$judgeStmt->execute([$entityId]);
				$judge = $judgeStmt->fetch(\PDO::FETCH_ASSOC);
				if (!$judge) { redirect('/admin/print-reports?error=judge_not_found'); return; }
				$subStmt = DB::pdo()->prepare('SELECT s.*, c.name as category_name FROM subcategories s JOIN categories c ON s.category_id = c.id JOIN subcategory_judges sj ON s.id = sj.subcategory_id WHERE sj.judge_id = ? ORDER BY c.name, s.name');
				$subStmt->execute([$entityId]);
				$subcategories = $subStmt->fetchAll(\PDO::FETCH_ASSOC);
				$scoresStmt = DB::pdo()->prepare('SELECT s.*, sc.name as subcategory_name, cr.name as criterion_name, con.name as contestant_name FROM scores s JOIN subcategories sc ON s.subcategory_id = sc.id JOIN criteria cr ON s.criterion_id = cr.id JOIN contestants con ON s.contestant_id = con.id WHERE s.judge_id = ? ORDER BY sc.name, con.name, cr.name');
				$scoresStmt->execute([$entityId]);
				$scores = $scoresStmt->fetchAll(\PDO::FETCH_ASSOC);
				$html = \App\render_to_string('print/judge', compact('judge','subcategories','scores'));
				$subject = 'Judge Report: ' . ($judge['name'] ?? '');
			} elseif ($reportType === 'category') {
				$catStmt = DB::pdo()->prepare('SELECT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id WHERE c.id = ?');
				$catStmt->execute([$entityId]);
				$category = $catStmt->fetch(\PDO::FETCH_ASSOC);
				if (!$category) { redirect('/admin/print-reports?error=category_not_found'); return; }
				$subStmt = DB::pdo()->prepare('SELECT * FROM subcategories WHERE category_id = ? ORDER BY name');
				$subStmt->execute([$entityId]);
				$subcategories = $subStmt->fetchAll(\PDO::FETCH_ASSOC);
				$scoresStmt = DB::pdo()->prepare('SELECT s.*, sc.name as subcategory_name, cr.name as criterion_name, con.name as contestant_name, j.name as judge_name FROM scores s JOIN subcategories sc ON s.subcategory_id = sc.id JOIN criteria cr ON s.criterion_id = cr.id JOIN contestants con ON s.contestant_id = con.id JOIN judges j ON s.judge_id = j.id WHERE sc.category_id = ? ORDER BY sc.name, con.name, cr.name, j.name');
				$scoresStmt->execute([$entityId]);
				$scores = $scoresStmt->fetchAll(\PDO::FETCH_ASSOC);
				$html = \App\render_to_string('print/category', compact('category','subcategories','scores'));
				$subject = 'Category Report: ' . ($category['name'] ?? '');
			} else {
				redirect('/admin/print-reports?error=invalid_report_type');
				return;
			}

			$sent = \App\Mailer::sendHtml($toEmail, $subject, $html);
			if ($sent) {
				\App\Logger::logAdminAction('email_report_sent', 'report', $entityId, "type={$reportType}; to={$toEmail}");
				redirect('/admin/print-reports?success=report_emailed');
			} else {
				\App\Logger::logAdminAction('email_report_failed', 'report', $entityId, "type={$reportType}; to={$toEmail}");
				redirect('/admin/print-reports?error=email_failed');
			}
		} catch (\Throwable $e) {
			\App\Logger::error('email_report_exception', 'report', $entityId, $e->getMessage());
			redirect('/admin/print-reports?error=email_exception');
		}
	}
	
	public function emceeScripts(): void {
		require_organizer();
		$scripts = DB::pdo()->query('SELECT es.*, u.preferred_name as uploaded_by_name FROM emcee_scripts es LEFT JOIN users u ON es.uploaded_by = u.id ORDER BY COALESCE(es.created_at, "1970-01-01 00:00:00") DESC')->fetchAll(\PDO::FETCH_ASSOC);
		view('admin/emcee_scripts', compact('scripts'));
	}
	
	public function uploadEmceeScript(): void {
		require_organizer();
		
		if (!isset($_FILES['script']) || $_FILES['script']['error'] !== UPLOAD_ERR_OK) {
			redirect('/admin/emcee-scripts?error=upload_failed');
			return;
		}
		
		$uploadDir = __DIR__ . '/../../public/uploads/emcee-scripts/';
		if (!is_dir($uploadDir)) {
			mkdir($uploadDir, 0755, true);
		}
		
		$filename = $_FILES['script']['name'];
		$filepath = $uploadDir . $filename;
		
		if (move_uploaded_file($_FILES['script']['tmp_name'], $filepath)) {
			$title = $_POST['title'] ?? '';
			$description = $_POST['description'] ?? '';
			$fileSize = $_FILES['script']['size'];
			$uploadedAt = date('Y-m-d H:i:s');
			
			$stmt = DB::pdo()->prepare('INSERT INTO emcee_scripts (id, filename, filepath, is_active, created_at, uploaded_by, title, description, file_name, file_size, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
			$stmt->execute([uuid(), $filename, '/uploads/emcee-scripts/' . $filename, 1, date('Y-m-d H:i:s'), $_SESSION['user']['id'], $title, $description, $filename, $fileSize, $uploadedAt]);
			redirect('/admin/emcee-scripts?success=script_uploaded');
		} else {
			redirect('/admin/emcee-scripts?error=upload_failed');
		}
	}
	
	public function deleteEmceeScript(array $params): void {
		require_organizer();
		$id = param('id', $params);
		
		$stmt = DB::pdo()->prepare('SELECT filepath FROM emcee_scripts WHERE id = ?');
		$stmt->execute([$id]);
		$filepath = $stmt->fetchColumn();
		
		if ($filepath && file_exists(__DIR__ . '/../../public' . $filepath)) {
			unlink(__DIR__ . '/../../public' . $filepath);
		}
		
		DB::pdo()->prepare('DELETE FROM emcee_scripts WHERE id = ?')->execute([$id]);
		redirect('/admin/emcee-scripts?success=script_deleted');
	}
	
	public function toggleEmceeScript(array $params): void {
		require_organizer();
		$id = param('id', $params);
		
		$stmt = DB::pdo()->prepare('UPDATE emcee_scripts SET is_active = NOT is_active WHERE id = ?');
		$stmt->execute([$id]);
		
		redirect('/admin/emcee-scripts?success=script_toggled');
	}
	
	public function contestantScores(array $params): void {
		require_organizer();
		$contestantId = param('contestantId', $params);
		
		// Get contestant info
		$contestant = DB::pdo()->prepare('SELECT * FROM contestants WHERE id = ?');
		$contestant->execute([$contestantId]);
		$contestant = $contestant->fetch(\PDO::FETCH_ASSOC);
		
		if (!$contestant) {
			redirect('/admin/users?error=contestant_not_found');
			return;
		}
		
		// Get all subcategories this contestant is assigned to
		$subcategories = DB::pdo()->prepare('
			SELECT s.*, c.name as category_name 
			FROM subcategories s 
			JOIN categories c ON s.category_id = c.id 
			JOIN subcategory_contestants sc ON s.id = sc.subcategory_id 
			WHERE sc.contestant_id = ? 
			ORDER BY c.name, s.name
		');
		$subcategories->execute([$contestantId]);
		$subcategories = $subcategories->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get all scores for this contestant
		$scores = DB::pdo()->prepare('
			SELECT s.*, sc.name as subcategory_name, cr.name as criterion_name, j.name as judge_name
			FROM scores s 
			JOIN subcategories sc ON s.subcategory_id = sc.id 
			JOIN criteria cr ON s.criterion_id = cr.id 
			JOIN judges j ON s.judge_id = j.id 
			WHERE s.contestant_id = ? 
			ORDER BY sc.name, cr.name, j.name
		');
		$scores->execute([$contestantId]);
		$scores = $scores->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get comments for this contestant
		$comments = DB::pdo()->prepare('
			SELECT jc.*, sc.name as subcategory_name, j.name as judge_name
			FROM judge_comments jc 
			JOIN subcategories sc ON jc.subcategory_id = sc.id 
			JOIN judges j ON jc.judge_id = j.id 
			WHERE jc.contestant_id = ? 
			ORDER BY sc.name, j.name
		');
		$comments->execute([$contestantId]);
		$comments = $comments->fetchAll(\PDO::FETCH_ASSOC);
		
		view('admin/contestant_scores', compact('contestant', 'subcategories', 'scores', 'comments'));
	}
}

class ProfileController {
	public function edit(): void {
		require_login();
		$user = current_user();
		view('profile/edit', compact('user'));
	}
	
	public function update(): void {
		require_login();
		$userId = $_SESSION['user']['id'];
		$name = post('name');
		$email = post('email') ?: null;
		$password = post('password');
		$preferredName = post('preferred_name') ?: $name;
		$gender = post('gender') ?: null;
		$theme = post('theme') ?: 'light';
		
		// Validate password complexity if provided
		if (!empty($password)) {
			if (strlen($password) < 8) {
				redirect('/profile?error=password_too_short');
				return;
			}
			if (!preg_match('/[A-Z]/', $password)) {
				redirect('/profile?error=password_no_uppercase');
				return;
			}
			if (!preg_match('/[a-z]/', $password)) {
				redirect('/profile?error=password_no_lowercase');
				return;
			}
			if (!preg_match('/[0-9]/', $password)) {
				redirect('/profile?error=password_no_number');
				return;
			}
			if (!preg_match('/[^A-Za-z0-9]/', $password)) {
				redirect('/profile?error=password_no_symbol');
				return;
			}
		}
		
		$passwordHash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
		
		if ($passwordHash) {
			$stmt = DB::pdo()->prepare('UPDATE users SET name = ?, email = ?, password_hash = ?, preferred_name = ?, gender = ?, theme = ? WHERE id = ?');
			$stmt->execute([$name, $email, $passwordHash, $preferredName, $gender, $theme, $userId]);
		} else {
			$stmt = DB::pdo()->prepare('UPDATE users SET name = ?, email = ?, preferred_name = ?, gender = ?, theme = ? WHERE id = ?');
			$stmt->execute([$name, $email, $preferredName, $gender, $theme, $userId]);
		}
		
		// Update session
		$_SESSION['user']['name'] = $name;
		$_SESSION['user']['email'] = $email;
		$_SESSION['user']['preferred_name'] = $preferredName;
		$_SESSION['user']['gender'] = $gender;
		$_SESSION['user']['theme'] = $theme;
		
		redirect('/profile?success=profile_updated');
	}
}

class PrintController {
	public function contestant(array $params): void {
		require_organizer();
		$contestantId = param('id', $params);
		
		// Get contestant info
		$contestant = DB::pdo()->prepare('SELECT * FROM contestants WHERE id = ?');
		$contestant->execute([$contestantId]);
		$contestant = $contestant->fetch(\PDO::FETCH_ASSOC);
		
		if (!$contestant) {
			redirect('/admin/print-reports?error=contestant_not_found');
			return;
		}
		
		// Get all subcategories this contestant is assigned to
		$subcategories = DB::pdo()->prepare('
			SELECT s.*, c.name as category_name 
			FROM subcategories s 
			JOIN categories c ON s.category_id = c.id 
			JOIN subcategory_contestants sc ON s.id = sc.subcategory_id 
			WHERE sc.contestant_id = ? 
			ORDER BY c.name, s.name
		');
		$subcategories->execute([$contestantId]);
		$subcategories = $subcategories->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get all scores for this contestant with contest and category info
		$scores = DB::pdo()->prepare('
			SELECT s.*, sc.name as subcategory_name, cr.name as criterion_name, cr.max_score, j.name as judge_name,
			       c.name as category_name, co.name as contest_name
			FROM scores s 
			JOIN subcategories sc ON s.subcategory_id = sc.id 
			JOIN categories c ON sc.category_id = c.id
			JOIN contests co ON c.contest_id = co.id
			JOIN criteria cr ON s.criterion_id = cr.id 
			JOIN judges j ON s.judge_id = j.id 
			WHERE s.contestant_id = ? 
			ORDER BY co.name, c.name, sc.name, cr.name, j.name
		');
		$scores->execute([$contestantId]);
		$scores = $scores->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get comments for this contestant
		$comments = DB::pdo()->prepare('
			SELECT jc.*, sc.name as subcategory_name, c.name as category_name, co.name as contest_name, j.name as judge_name
			FROM judge_comments jc 
			JOIN subcategories sc ON jc.subcategory_id = sc.id 
			JOIN categories c ON sc.category_id = c.id
			JOIN contests co ON c.contest_id = co.id
			JOIN judges j ON jc.judge_id = j.id 
			WHERE jc.contestant_id = ? 
			ORDER BY co.name, c.name, sc.name, j.name
		');
		$comments->execute([$contestantId]);
		$comments = $comments->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get overall deductions for this contestant
		$deductions = DB::pdo()->prepare('
			SELECT od.*, sc.name as subcategory_name
			FROM overall_deductions od
			JOIN subcategories sc ON od.subcategory_id = sc.id
			WHERE od.contestant_id = ?
			ORDER BY sc.name
		');
		$deductions->execute([$contestantId]);
		$deductions = $deductions->fetchAll(\PDO::FETCH_ASSOC);
		
		view('print/contestant', compact('contestant', 'subcategories', 'scores', 'comments', 'deductions'));
	}
	
	public function judge(array $params): void {
		require_organizer();
		$judgeId = param('id', $params);
		
		// Get judge info
		$judge = DB::pdo()->prepare('SELECT * FROM judges WHERE id = ?');
		$judge->execute([$judgeId]);
		$judge = $judge->fetch(\PDO::FETCH_ASSOC);
		
		if (!$judge) {
			redirect('/admin/print-reports?error=judge_not_found');
			return;
		}
		
		// Get all subcategories this judge is assigned to
		$subcategories = DB::pdo()->prepare('
			SELECT s.*, c.name as category_name 
			FROM subcategories s 
			JOIN categories c ON s.category_id = c.id 
			JOIN subcategory_judges sj ON s.id = sj.subcategory_id 
			WHERE sj.judge_id = ? 
			ORDER BY c.name, s.name
		');
		$subcategories->execute([$judgeId]);
		$subcategories = $subcategories->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get all scores for this judge
		$scores = DB::pdo()->prepare('
			SELECT s.*, sc.name as subcategory_name, cr.name as criterion_name, con.name as contestant_name
			FROM scores s 
			JOIN subcategories sc ON s.subcategory_id = sc.id 
			JOIN criteria cr ON s.criterion_id = cr.id 
			JOIN contestants con ON s.contestant_id = con.id 
			WHERE s.judge_id = ? 
			ORDER BY sc.name, con.name, cr.name
		');
		$scores->execute([$judgeId]);
		$scores = $scores->fetchAll(\PDO::FETCH_ASSOC);
		
		view('print/judge', compact('judge', 'subcategories', 'scores'));
	}
	
	public function category(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		
		// Get category info
		$category = DB::pdo()->prepare('SELECT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id WHERE c.id = ?');
		$category->execute([$categoryId]);
		$category = $category->fetch(\PDO::FETCH_ASSOC);
		
		if (!$category) {
			redirect('/admin/print-reports?error=category_not_found');
			return;
		}
		
		// Get all subcategories for this category
		$subcategories = DB::pdo()->prepare('SELECT * FROM subcategories WHERE category_id = ? ORDER BY name');
		$subcategories->execute([$categoryId]);
		$subcategories = $subcategories->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get all scores for this category
		$scores = DB::pdo()->prepare('
			SELECT s.*, sc.name as subcategory_name, cr.name as criterion_name, con.name as contestant_name, j.name as judge_name
			FROM scores s 
			JOIN subcategories sc ON s.subcategory_id = sc.id 
			JOIN criteria cr ON s.criterion_id = cr.id 
			JOIN contestants con ON s.contestant_id = con.id 
			JOIN judges j ON s.judge_id = j.id 
			WHERE sc.category_id = ? 
			ORDER BY sc.name, con.name, cr.name, j.name
		');
		$scores->execute([$categoryId]);
		$scores = $scores->fetchAll(\PDO::FETCH_ASSOC);
		
		view('print/category', compact('category', 'subcategories', 'scores'));
	}
}

class EmceeController {
	public function index(): void {
		require_emcee();
		
		// Get active emcee scripts
		$scripts = DB::pdo()->query('SELECT *, COALESCE(created_at, "Unknown") as created_at FROM emcee_scripts WHERE is_active = 1 ORDER BY COALESCE(created_at, "1970-01-01 00:00:00") DESC')->fetchAll(\PDO::FETCH_ASSOC);
		
		// Get all contestants with numbers
		$contestants = DB::pdo()->query('SELECT * FROM contestants WHERE contestant_number IS NOT NULL ORDER BY contestant_number')->fetchAll(\PDO::FETCH_ASSOC);
		
		view('emcee/index', compact('scripts', 'contestants'));
	}
	
	public function streamScript(array $params): void {
		require_emcee();
		$scriptId = param('id', $params);
		
		$stmt = DB::pdo()->prepare('SELECT * FROM emcee_scripts WHERE id = ? AND is_active = 1');
		$stmt->execute([$scriptId]);
		$script = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$script) {
			http_response_code(404);
			echo 'Script not found';
			return;
		}
		
		$filepath = __DIR__ . '/../../public' . $script['filepath'];
		if (!file_exists($filepath)) {
			http_response_code(404);
			echo 'File not found';
			return;
		}
		
		$mimeType = mime_content_type($filepath);
		header('Content-Type: ' . $mimeType);
		header('Content-Disposition: inline; filename="' . $script['filename'] . '"');
		readfile($filepath);
	}
	
	public function contestantBio(array $params): void {
		require_emcee();
		$number = param('number', $params);
		
		$stmt = DB::pdo()->prepare('SELECT * FROM contestants WHERE contestant_number = ?');
		$stmt->execute([$number]);
		$contestant = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$contestant) {
			http_response_code(404);
			echo 'Contestant not found';
			return;
		}
		
		view('emcee/contestant_bio', compact('contestant'));
	}
	
	public function judgesByCategory(): void {
		require_emcee();
		
		// Get judges grouped by category
		$judges = DB::pdo()->query('
			SELECT j.id, j.name as judge_name, j.image_path, j.bio, j.email, j.is_head_judge, c.name as category_name, c.id as category_id
			FROM judges j
			JOIN category_judges cj ON j.id = cj.judge_id
			JOIN categories c ON cj.category_id = c.id
			ORDER BY c.name, j.name
		')->fetchAll(\PDO::FETCH_ASSOC);
		
		// Group judges by category
		$groupedJudges = [];
		foreach ($judges as $judge) {
			$groupedJudges[$judge['category_name']][] = $judge;
		}
		
		view('emcee/judges', compact('groupedJudges'));
	}
}

class TemplateController {
	public function index(): void {
		require_organizer();
		$rows = DB::pdo()->query('SELECT * FROM subcategory_templates ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		view('templates/index', compact('rows'));
	}
	
	public function new(): void {
		require_organizer();
		view('templates/new');
	}
	
	public function create(): void {
		require_organizer();
		$name = post('name');
		$description = post('description') ?: null;
		$subcategoryNames = post('subcategory_names');
		$maxScore = (int)post('max_score') ?: 60;
		
		// Parse subcategory names
		$names = array_filter(array_map('trim', explode("\n", $subcategoryNames)));
		
		$stmt = DB::pdo()->prepare('INSERT INTO subcategory_templates (id, name, description, subcategory_names, max_score) VALUES (?, ?, ?, ?, ?)');
		$stmt->execute([uuid(), $name, $description, json_encode($names), $maxScore]);
		
		redirect('/admin/templates?success=template_created');
	}
	
	public function delete(array $params): void {
		require_organizer();
		$id = param('id', $params);
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Delete template criteria first
			$pdo->prepare('DELETE FROM template_criteria WHERE template_id = ?')->execute([$id]);
			// Delete template
			$pdo->prepare('DELETE FROM subcategory_templates WHERE id = ?')->execute([$id]);
			
			$pdo->commit();
			redirect('/admin/templates?success=template_deleted');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/admin/templates?error=delete_failed');
		}
	}
}

class CategoryAssignmentController {
	public function edit(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$category = DB::pdo()->prepare('SELECT * FROM categories WHERE id = ?');
		$category->execute([$categoryId]);
		$category = $category->fetch(\PDO::FETCH_ASSOC);
		$contestants = DB::pdo()->query('SELECT * FROM contestants ORDER BY contestant_number IS NULL, contestant_number, name')->fetchAll(\PDO::FETCH_ASSOC);
		$judges = DB::pdo()->query('SELECT * FROM judges ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		$assignedContestants = DB::pdo()->prepare('SELECT contestant_id FROM category_contestants WHERE category_id = ?');
		$assignedContestants->execute([$categoryId]);
		$assignedContestants = array_column($assignedContestants->fetchAll(\PDO::FETCH_ASSOC), 'contestant_id');
		$assignedJudges = DB::pdo()->prepare('SELECT judge_id FROM category_judges WHERE category_id = ?');
		$assignedJudges->execute([$categoryId]);
		$assignedJudges = array_column($assignedJudges->fetchAll(\PDO::FETCH_ASSOC), 'judge_id');
		view('category_assignments/edit', compact('category','contestants','judges','assignedContestants','assignedJudges'));
	}
	
	public function update(array $params): void {
		require_organizer();
		$categoryId = param('id', $params);
		$contestants = request_array('contestants');
		$judges = request_array('judges');
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		$pdo->prepare('DELETE FROM category_contestants WHERE category_id = ?')->execute([$categoryId]);
		$pdo->prepare('DELETE FROM category_judges WHERE category_id = ?')->execute([$categoryId]);
		$insC = $pdo->prepare('INSERT INTO category_contestants (category_id, contestant_id) VALUES (?, ?)');
		$insJ = $pdo->prepare('INSERT INTO category_judges (category_id, judge_id) VALUES (?, ?)');
		foreach ($contestants as $id) { if ($id) $insC->execute([$categoryId, $id]); }
		foreach ($judges as $id) { if ($id) $insJ->execute([$categoryId, $id]); }
		$pdo->commit();
		$_SESSION['success_message'] = 'Category assignments updated successfully!';
		redirect('/categories/' . $categoryId . '/assign');
	}
}

