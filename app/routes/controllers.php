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
		$stmt = DB::pdo()->prepare('INSERT INTO contests (id, name, start_date, end_date) VALUES (?, ?, ?, ?)');
		$stmt->execute([uuid(), post('name'), post('start_date'), post('end_date')]);
		redirect('/contests');
	}
	
	public function archive(array $params): void {
		require_organizer();
		$contestId = param('id', $params);
		
		// Get contest information
		$stmt = DB::pdo()->prepare('SELECT * FROM contests WHERE id = ?');
		$stmt->execute([$contestId]);
		$contest = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$contest) {
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
			\App\Logger::logContestArchive($contestId, $contest['name']);
			redirect('/contests?success=contest_archived');
		} catch (\Exception $e) {
			$pdo->rollBack();
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
		
		// Get archived contest details
		$stmt = DB::pdo()->prepare('SELECT * FROM archived_contests WHERE id = ?');
		$stmt->execute([$archivedContestId]);
		$archivedContest = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$archivedContest) {
			redirect('/admin/archived-contests?error=contest_not_found');
			return;
		}
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			$reactivatedBy = $_SESSION['user']['name'] ?? 'Unknown';
			$newContestId = uuid();
			
			// Create new contest
			$stmt = $pdo->prepare('INSERT INTO contests (id, name, description, start_date, end_date) VALUES (?, ?, ?, ?, ?)');
			$stmt->execute([$newContestId, $archivedContest['name'], $archivedContest['description'], $archivedContest['start_date'], $archivedContest['end_date']]);
			
			// Get all archived categories for this contest
			$stmt = $pdo->prepare('SELECT * FROM archived_categories WHERE archived_contest_id = ?');
			$stmt->execute([$archivedContestId]);
			$archivedCategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			foreach ($archivedCategories as $archivedCategory) {
				$newCategoryId = uuid();
				
				// Create new category
				$stmt = $pdo->prepare('INSERT INTO categories (id, contest_id, name, description) VALUES (?, ?, ?, ?)');
				$stmt->execute([$newCategoryId, $newContestId, $archivedCategory['name'], $archivedCategory['description']]);
				
				// Get all archived subcategories for this category
				$stmt = $pdo->prepare('SELECT * FROM archived_subcategories WHERE archived_category_id = ?');
				$stmt->execute([$archivedCategory['id']]);
				$archivedSubcategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				
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
			\App\Logger::logAdminAction('contest_reactivated', 'contest', $newContestId, "Contest '{$archivedContest['name']}' reactivated from archive by {$reactivatedBy}");
			redirect('/contests?success=contest_reactivated');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/admin/archived-contests?error=reactivation_failed');
		}
	}
}

class BackupController {
	public function index(): void {
		require_organizer();
		
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
			$stmt = $pdo->prepare('INSERT INTO backup_settings (id, backup_type, enabled, frequency, retention_days) VALUES (?, ?, ?, ?, ?)');
			$stmt->execute([uuid(), 'schema', 0, 'daily', 30]);
			$stmt->execute([uuid(), 'full', 0, 'weekly', 30]);
			
			// Re-fetch the settings
			$stmt = $pdo->query('SELECT * FROM backup_settings ORDER BY backup_type');
			$backupSettings = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		}
		
		$backupDirectory = $this->getBackupDirectory();
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
		
		// Debug: Log the POST data
		\App\Logger::debug('Backup settings update POST data: ' . json_encode($_POST));
		
		$schemaEnabled = isset($_POST['schema_enabled']) ? 1 : 0;
		$schemaFrequency = $_POST['schema_frequency'] ?? 'daily';
		$schemaFrequencyValue = (int)($_POST['schema_frequency_value'] ?? 1);
		$schemaRetention = (int)($_POST['schema_retention'] ?? 30);
		
		$fullEnabled = isset($_POST['full_enabled']) ? 1 : 0;
		$fullFrequency = $_POST['full_frequency'] ?? 'weekly';
		$fullFrequencyValue = (int)($_POST['full_frequency_value'] ?? 1);
		$fullRetention = (int)($_POST['full_retention'] ?? 30);
		
		// Debug: Log the parsed values
		\App\Logger::debug("Parsed values - Schema: enabled={$schemaEnabled}, frequency={$schemaFrequency}, value={$schemaFrequencyValue}, retention={$schemaRetention}");
		\App\Logger::debug("Parsed values - Full: enabled={$fullEnabled}, frequency={$fullFrequency}, value={$fullFrequencyValue}, retention={$fullRetention}");
		
		try {
			$pdo = DB::pdo();
			$pdo->beginTransaction();
			
			// Update schema backup settings
			$stmt = $pdo->prepare('UPDATE backup_settings SET enabled = ?, frequency = ?, frequency_value = ?, retention_days = ?, updated_at = CURRENT_TIMESTAMP WHERE backup_type = ?');
			$stmt->execute([$schemaEnabled, $schemaFrequency, $schemaFrequencyValue, $schemaRetention, 'schema']);
			
			// Update full backup settings
			$stmt = $pdo->prepare('UPDATE backup_settings SET enabled = ?, frequency = ?, frequency_value = ?, retention_days = ?, updated_at = CURRENT_TIMESTAMP WHERE backup_type = ?');
			$stmt->execute([$fullEnabled, $fullFrequency, $fullFrequencyValue, $fullRetention, 'full']);
			
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
						
						// Update next run time
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
			
			// Get all backup settings (enabled and disabled)
			$stmt = $pdo->query('SELECT * FROM backup_settings ORDER BY backup_type');
			$settings = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			
			$debugInfo = [
				'current_time' => $now,
				'current_timestamp' => strtotime($now),
				'settings' => []
			];
			
			foreach ($settings as $setting) {
				$nextRun = $setting['next_run'];
				$nextRunTimestamp = $nextRun ? strtotime($nextRun) : null;
				$shouldRun = false;
				
				if (empty($nextRun)) {
					$shouldRun = true;
				} else {
					$shouldRun = strtotime($now) >= $nextRunTimestamp;
				}
				
				$debugInfo['settings'][] = [
					'backup_type' => $setting['backup_type'],
					'enabled' => $setting['enabled'],
					'frequency' => $setting['frequency'],
					'frequency_value' => $setting['frequency_value'],
					'last_run' => $setting['last_run'],
					'next_run' => $nextRun,
					'next_run_timestamp' => $nextRunTimestamp,
					'should_run' => $shouldRun,
					'calculated_next_run' => $this->calculateNextRun($setting['frequency'], $setting['frequency_value'] ?? 1)
				];
			}
			
			echo '<pre>' . print_r($debugInfo, true) . '</pre>';
			exit;
		} catch (\Exception $e) {
			echo '<pre>Error: ' . $e->getMessage() . '</pre>';
			exit;
		}
	}
	
	public function testDatabaseConstraint(): void {
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
		$stmt = DB::pdo()->prepare('INSERT INTO categories (id, contest_id, name) VALUES (?, ?, ?)');
		$stmt->execute([uuid(), $contestId, post('name')]);
		redirect('/contests/' . $contestId . '/categories');
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
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		$subcategoryId = uuid();
		$stmt = $pdo->prepare('INSERT INTO subcategories (id, category_id, name, description, score_cap) VALUES (?, ?, ?, ?, ?)');
		$stmt->execute([$subcategoryId, $categoryId, post('name'), post('description') ?: null, post('score_cap') ?: null]);
		
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
		
		// Create a default criterion with max score 60 if none exist
		$insC = $pdo->prepare('INSERT INTO criteria (id, subcategory_id, name, max_score) VALUES (?, ?, ?, ?)');
		$insC->execute([uuid(), $subcategoryId, 'Overall Performance', 60]);
		
		$pdo->commit();
		redirect('/categories/' . $categoryId . '/subcategories');
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
		$contestants = DB::pdo()->query('SELECT * FROM contestants ORDER BY contestant_number IS NULL, contestant_number, name')->fetchAll(\PDO::FETCH_ASSOC);
		$judges = DB::pdo()->query('SELECT * FROM judges ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
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
		$_SESSION['success_message'] = 'Contestant updated successfully!';
		redirect('/people');
	}
	public function deleteContestant(array $params): void {
		require_organizer();
		$id = param('id', $params);
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Delete associated image file
			$stmt = $pdo->prepare('SELECT image_path FROM contestants WHERE id = ?');
			$stmt->execute([$id]);
			$imagePath = $stmt->fetchColumn();
			if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
				unlink(__DIR__ . '/../../public' . $imagePath);
			}
			
			// Delete all related data
			$pdo->prepare('DELETE FROM judge_comments WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM scores WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM subcategory_contestants WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM category_contestants WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM contestants WHERE id = ?')->execute([$id]);
			
			$pdo->commit();
			$_SESSION['success_message'] = 'Contestant and all associated data deleted successfully!';
			redirect('/people');
		} catch (\Exception $e) {
			$pdo->rollBack();
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
		$_SESSION['success_message'] = 'Judge updated successfully!';
		redirect('/people');
	}
	public function deleteJudge(array $params): void {
		require_organizer();
		$id = param('id', $params);
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Delete associated image file
			$stmt = $pdo->prepare('SELECT image_path FROM judges WHERE id = ?');
			$stmt->execute([$id]);
			$imagePath = $stmt->fetchColumn();
			if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
				unlink(__DIR__ . '/../../public' . $imagePath);
			}
			
			// Delete all related data
			$pdo->prepare('DELETE FROM judge_certifications WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM judge_comments WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM scores WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM subcategory_judges WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM category_judges WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM judges WHERE id = ?')->execute([$id]);
			
			$pdo->commit();
			$_SESSION['success_message'] = 'Judge and all associated data deleted successfully!';
			redirect('/people');
		} catch (\Exception $e) {
			$pdo->rollBack();
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
		
		// Auto-generate criterion name
		$stmt = DB::pdo()->prepare('SELECT COUNT(*) as count FROM criteria WHERE subcategory_id = ?');
		$stmt->execute([$subcategoryId]);
		$count = $stmt->fetch(\PDO::FETCH_ASSOC)['count'];
		$criterionName = 'Criterion ' . ($count + 1);
		
		$stmt = DB::pdo()->prepare('INSERT INTO criteria (id, subcategory_id, name, max_score) VALUES (?, ?, ?, ?)');
		$stmt->execute([uuid(), $subcategoryId, $criterionName, (int)$maxScore]);
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
		if (is_judge()) {
			$allowed = DB::pdo()->prepare('SELECT 1 FROM subcategory_judges WHERE subcategory_id = ? AND judge_id = ?');
			$allowed->execute([$subcategoryId, $judgeId]);
			if (!$allowed->fetchColumn()) { http_response_code(403); echo 'Forbidden'; return; }
			// Prevent edits after certification for this specific contestant
			$chk = DB::pdo()->prepare('SELECT 1 FROM judge_certifications WHERE subcategory_id=? AND contestant_id=? AND judge_id=?');
			$chk->execute([$subcategoryId, $contestantId, $judgeId]);
			if ($chk->fetchColumn()) { http_response_code(423); echo 'Locked'; return; }
		}
		$scores = $_POST['scores'] ?? [];
		$comments = $_POST['comments'] ?? [];
		
		$pdo = DB::pdo();
		$now = date('c');
		$pdo->beginTransaction();
		
		// Handle scores - they come as scores[criterion_id] = value
		foreach ($scores as $criterionId => $value) {
			if ($value !== '' && $value !== null) {
				$stmt = $pdo->prepare('INSERT OR REPLACE INTO scores (id, subcategory_id, contestant_id, judge_id, criterion_id, score, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
				$stmt->execute([uuid(), $subcategoryId, $contestantId, $judgeId, $criterionId, (float)$value, $now]);
			}
		}
		
		// Handle comments - they come as comments[contestant_id] = text
		foreach ($comments as $commentContestantId => $text) {
			if ($text !== '' && $text !== null) {
				$stmt = $pdo->prepare('INSERT OR REPLACE INTO judge_comments (id, subcategory_id, contestant_id, judge_id, comment, created_at) VALUES (?, ?, ?, ?, ?, ?)');
				$stmt->execute([uuid(), $subcategoryId, $commentContestantId, $judgeId, (string)$text, $now]);
			}
		}
		if (is_judge()) {
			$signature = trim((string)post('signature_name'));
			if ($signature === '') { 
				// Use preferred name as default signature
				$signature = current_user()['preferred_name'] ?? current_user()['name'];
			}
			
			// Validate signature matches judge's preferred name
			$judgePreferredName = current_user()['preferred_name'] ?? current_user()['name'];
			if (strtolower(trim($signature)) !== strtolower(trim($judgePreferredName))) {
				$pdo->rollback();
				redirect('/score/' . $subcategoryId . '/contestant/' . $contestantId . '?error=signature_mismatch');
				return;
			}
			
			$pdo->prepare('INSERT OR REPLACE INTO judge_certifications (id, subcategory_id, contestant_id, judge_id, signature_name, certified_at) VALUES (?,?,?,?,?,?)')
				->execute([uuid(), $subcategoryId, $contestantId, $judgeId, $signature, $now]);
		}
		$pdo->commit();
		
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
		
		if (empty($email) || empty($password)) {
			redirect('/login?error=missing_fields');
			return;
		}
		
		// Try to find user by email or preferred name
		$stmt = DB::pdo()->prepare('SELECT * FROM users WHERE email = ? OR preferred_name = ?');
		$stmt->execute([$email, $email]);
		$user = $stmt->fetch(\PDO::FETCH_ASSOC);
		
		if (!$user || !password_verify($password, $user['password_hash'])) {
			\App\Logger::logLogin($email, false);
			redirect('/login?error=invalid_credentials');
			return;
		}
		
		// Check if user's session has been invalidated
		if ($user['session_version'] !== ($_SESSION['session_version'] ?? '')) {
			\App\Logger::logLogin($user['email'] ?? $user['preferred_name'], false, 'session_invalidated');
			redirect('/login?error=session_invalidated');
			return;
		}
		
		$_SESSION['user'] = $user;
		$_SESSION['session_version'] = $user['session_version'];
		
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
	}
	
	public function logout(): void {
		if (isset($_SESSION['user'])) {
			\App\Logger::logLogout($_SESSION['user']['email'] ?? $_SESSION['user']['preferred_name']);
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
		
		// Validate required fields
		if (empty($name) || empty($role)) {
			redirect('/users/new?error=missing_fields');
			return;
		}
		
		// Validate password complexity if provided
		if (!empty($password)) {
			if (strlen($password) < 8) {
				redirect('/users/new?error=password_too_short');
				return;
			}
			if (!preg_match('/[A-Z]/', $password)) {
				redirect('/users/new?error=password_no_uppercase');
				return;
			}
			if (!preg_match('/[a-z]/', $password)) {
				redirect('/users/new?error=password_no_lowercase');
				return;
			}
			if (!preg_match('/[0-9]/', $password)) {
				redirect('/users/new?error=password_no_number');
				return;
			}
			if (!preg_match('/[^A-Za-z0-9]/', $password)) {
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
			
			// Handle role-specific creation
			if ($role === 'judge') {
				$judgeId = uuid();
				$stmt = $pdo->prepare('INSERT INTO judges (id, name, email, gender, is_head_judge) VALUES (?, ?, ?, ?, ?)');
				$stmt->execute([$judgeId, $name, $email, $gender, $isHeadJudge]);
				
				// Link user to judge
				$stmt = $pdo->prepare('UPDATE users SET judge_id = ? WHERE id = ?');
				$stmt->execute([$judgeId, $userId]);
				
				// Assign to category if provided
				if ($categoryId) {
					$stmt = $pdo->prepare('INSERT INTO category_judges (category_id, judge_id) VALUES (?, ?)');
					$stmt->execute([$categoryId, $judgeId]);
				}
			} elseif ($role === 'contestant') {
				$contestantId = uuid();
				$stmt = $pdo->prepare('INSERT INTO contestants (id, name, email, gender) VALUES (?, ?, ?, ?)');
				$stmt->execute([$contestantId, $name, $email, $gender]);
				
				// Link user to contestant
				$stmt = $pdo->prepare('UPDATE users SET contestant_id = ? WHERE id = ?');
				$stmt->execute([$contestantId, $userId]);
				
				// Assign to category if provided
				if ($categoryId) {
					$stmt = $pdo->prepare('INSERT INTO category_contestants (category_id, contestant_id) VALUES (?, ?)');
					$stmt->execute([$categoryId, $contestantId]);
				}
			}
			
			$pdo->commit();
			\App\Logger::logUserCreation($userId, $name, $role);
			redirect('/admin/users?success=user_created');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/users/new?error=creation_failed');
		}
	}
	
	public function index(): void {
		require_organizer();
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
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Get user info for logging
			$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
			$stmt->execute([$id]);
			$user = $stmt->fetch(\PDO::FETCH_ASSOC);
			
			if (!$user) {
				redirect('/admin/users?error=user_not_found');
				return;
			}
			
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
			\App\Logger::logUserDeletion($id, $user['name'], $user['role']);
			redirect('/admin/users?success=user_deleted');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/admin/users?error=delete_failed');
		}
	}
	
	public function removeAllJudges(): void {
		require_organizer();
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Get all judge users
			$judgeUsers = $pdo->query('SELECT * FROM users WHERE role = "judge"')->fetchAll(\PDO::FETCH_ASSOC);
			
			foreach ($judgeUsers as $user) {
				if ($user['judge_id']) {
					// Delete associated image file
					$stmt = $pdo->prepare('SELECT image_path FROM judges WHERE id = ?');
					$stmt->execute([$user['judge_id']]);
					$imagePath = $stmt->fetchColumn();
					if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
						unlink(__DIR__ . '/../../public' . $imagePath);
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
			redirect('/admin/users?success=all_judges_removed');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/admin/users?error=remove_failed');
		}
	}
	
	public function removeAllContestants(): void {
		require_organizer();
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Get all contestant users
			$contestantUsers = $pdo->query('SELECT * FROM users WHERE role = "contestant"')->fetchAll(\PDO::FETCH_ASSOC);
			
			foreach ($contestantUsers as $user) {
				if ($user['contestant_id']) {
					// Delete associated image file
					$stmt = $pdo->prepare('SELECT image_path FROM contestants WHERE id = ?');
					$stmt->execute([$user['contestant_id']]);
					$imagePath = $stmt->fetchColumn();
					if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
						unlink(__DIR__ . '/../../public' . $imagePath);
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
			redirect('/admin/users?success=all_contestants_removed');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/admin/users?error=remove_failed');
		}
	}
	
	public function removeAllEmcees(): void {
		require_organizer();
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Delete all emcee users
			$pdo->prepare('DELETE FROM users WHERE role = "emcee"')->execute();
			
			$pdo->commit();
			redirect('/admin/users?success=all_emcees_removed');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/admin/users?error=remove_failed');
		}
	}
	
	public function forceRefresh(): void {
		require_organizer();
		redirect('/admin/users?success=tables_refreshed');
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
		
		$stmt = DB::pdo()->prepare('INSERT INTO judges (id, name, email, gender, is_head_judge) VALUES (?, ?, ?, ?, ?)');
		$stmt->execute([uuid(), $name, $email, $gender, $isHeadJudge]);
		
		redirect('/admin/judges?success=judge_created');
	}
	
	public function updateJudge(array $params): void {
		require_organizer();
		$id = param('id', $params);
		$name = post('name');
		$email = post('email') ?: null;
		$gender = post('gender') ?: null;
		$isHeadJudge = post('is_head_judge') ? 1 : 0;
		
		$stmt = DB::pdo()->prepare('UPDATE judges SET name = ?, email = ?, gender = ?, is_head_judge = ? WHERE id = ?');
		$stmt->execute([$name, $email, $gender, $isHeadJudge, $id]);
		
		redirect('/admin/judges?success=judge_updated');
	}
	
	public function deleteJudge(): void {
		require_organizer();
		$id = post('judge_id');
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Delete associated image file
			$stmt = $pdo->prepare('SELECT image_path FROM judges WHERE id = ?');
			$stmt->execute([$id]);
			$imagePath = $stmt->fetchColumn();
			if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
				unlink(__DIR__ . '/../../public' . $imagePath);
			}
			
			// Delete all related data
			$pdo->prepare('DELETE FROM judge_certifications WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM judge_comments WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM scores WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM subcategory_judges WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM category_judges WHERE judge_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM judges WHERE id = ?')->execute([$id]);
			
			$pdo->commit();
			redirect('/admin/judges?success=judge_deleted');
		} catch (\Exception $e) {
			$pdo->rollBack();
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
		
		$stmt = DB::pdo()->prepare('INSERT INTO contestants (id, name, email, gender, contestant_number) VALUES (?, ?, ?, ?, ?)');
		$stmt->execute([uuid(), $name, $email, $gender, $contestantNumber]);
		
		redirect('/admin/contestants?success=contestant_created');
	}
	
	public function deleteContestant(): void {
		require_organizer();
		$id = post('contestant_id');
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Delete associated image file
			$stmt = $pdo->prepare('SELECT image_path FROM contestants WHERE id = ?');
			$stmt->execute([$id]);
			$imagePath = $stmt->fetchColumn();
			if ($imagePath && file_exists(__DIR__ . '/../../public' . $imagePath)) {
				unlink(__DIR__ . '/../../public' . $imagePath);
			}
			
			// Delete all related data
			$pdo->prepare('DELETE FROM judge_comments WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM scores WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM subcategory_contestants WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM category_contestants WHERE contestant_id = ?')->execute([$id]);
			$pdo->prepare('DELETE FROM contestants WHERE id = ?')->execute([$id]);
			
			$pdo->commit();
			redirect('/admin/contestants?success=contestant_deleted');
		} catch (\Exception $e) {
			$pdo->rollBack();
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
		
		$passwordHash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
		
		$stmt = DB::pdo()->prepare('INSERT INTO users (id, name, email, password_hash, role, preferred_name, gender) VALUES (?, ?, ?, ?, ?, ?, ?)');
		$stmt->execute([uuid(), $name, $email, $passwordHash, 'organizer', $preferredName, $gender]);
		
		redirect('/admin/organizers?success=organizer_created');
	}
	
	public function deleteOrganizer(): void {
		require_organizer();
		$id = post('organizer_id');
		
		// Don't allow deleting yourself
		if ($id === ($_SESSION['user']['id'] ?? '')) {
			redirect('/admin/organizers?error=cannot_delete_self');
			return;
		}
		
		DB::pdo()->prepare('DELETE FROM users WHERE id = ? AND role = "organizer"')->execute([$id]);
		redirect('/admin/organizers?success=organizer_deleted');
	}
	
	public function settings(): void {
		require_organizer();
		
		// Get current settings
		$settings = [];
		$stmt = DB::pdo()->query('SELECT setting_key, setting_value FROM system_settings');
		while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
			$settings[$row['setting_key']] = $row['setting_value'];
		}
		
		view('admin/settings', compact('settings'));
	}
	
	public function updateSettings(): void {
		require_organizer();
		
		$sessionTimeout = (int)post('session_timeout');
		$logLevel = post('log_level');
		
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Update session timeout
			$stmt = $pdo->prepare('INSERT OR REPLACE INTO system_settings (setting_key, setting_value) VALUES (?, ?)');
			$stmt->execute(['session_timeout', $sessionTimeout]);
			
			// Update log level
			$stmt = $pdo->prepare('INSERT OR REPLACE INTO system_settings (setting_key, setting_value) VALUES (?, ?)');
			$stmt->execute(['log_level', $logLevel]);
			
			$pdo->commit();
			redirect('/admin/settings?success=settings_updated');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/admin/settings?error=update_failed');
		}
	}
	
	public function logs(): void {
		require_organizer();
		
		// Get filter parameters
		$logLevel = $_GET['log_level'] ?? '';
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
		
		// Get current log level setting
		$stmt = DB::pdo()->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
		$stmt->execute(['log_level']);
		$currentLogLevel = $stmt->fetchColumn() ?: 'INFO';
		
		// Calculate total pages
		$totalPages = ceil($totalLogs / $perPage);
		
		// Get available roles and actions for filter dropdowns
		$availableRoles = ['organizer', 'judge', 'emcee', 'contestant'];
		$stmt = DB::pdo()->query('SELECT DISTINCT action FROM activity_logs ORDER BY action');
		$availableActions = $stmt->fetchAll(\PDO::FETCH_COLUMN);
		
		view('admin/logs', compact('logs', 'totalLogs', 'totalPages', 'page', 'perPage', 'currentLogLevel', 'logLevel', 'userRole', 'action', 'dateFrom', 'dateTo', 'availableRoles', 'availableActions'));
	}
	
	public function forceLogoutAll(): void {
		require_organizer();
		
		// Increment session version for all users
		DB::pdo()->prepare('UPDATE users SET session_version = ?')->execute([uuid()]);
		
		\App\Logger::logAdminAction('force_logout_all', 'system', '', 'All users logged out');
		redirect('/admin/users?success=all_users_logged_out');
	}
	
	public function forceLogoutUser(array $params): void {
		require_organizer();
		$userId = param('id', $params);
		
		// Generate new session version for this user
		$newSessionVersion = uuid();
		DB::pdo()->prepare('UPDATE users SET session_version = ? WHERE id = ?')->execute([$newSessionVersion, $userId]);
		
		\App\Logger::logAdminAction('force_logout_user', 'user', $userId, 'User logged out');
		redirect('/admin/users?success=user_logged_out');
	}
	
	public function printReports(): void {
		require_organizer();
		
		// Get all contestants, judges, and categories for print options
		$contestants = DB::pdo()->query('SELECT * FROM contestants ORDER BY contestant_number IS NULL, contestant_number, name')->fetchAll(\PDO::FETCH_ASSOC);
		$judges = DB::pdo()->query('SELECT * FROM judges ORDER BY name')->fetchAll(\PDO::FETCH_ASSOC);
		$structure = DB::pdo()->query('SELECT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id ORDER BY co.name, c.name')->fetchAll(\PDO::FETCH_ASSOC);
		
		view('admin/print_reports', compact('contestants', 'judges', 'structure'));
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
			SELECT j.*, c.name as category_name, c.id as category_id
			FROM judges j
			JOIN category_judges cj ON j.id = cj.judge_id
			JOIN categories c ON cj.category_id = c.id
			ORDER BY c.name, j.name
		')->fetchAll(\PDO::FETCH_ASSOC);
		
		view('emcee/judges', compact('judges'));
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

