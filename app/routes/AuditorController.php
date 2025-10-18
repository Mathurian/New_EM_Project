<?php
declare(strict_types=1);

namespace App\Routes;

use function App\{view, redirect, require_auditor, require_csrf, url, current_user, is_auditor, is_organizer, is_tally_master};
use App\DB;
use App\Logger;

class AuditorController {
	public function index(): void {
		require_auditor();
		view('auditor/index');
	}
	
	public function scores(): void {
		require_auditor();
		
		// Get all scores with contestant, category, and subcategory information
		$sql = "
			SELECT 
				s.*,
				c.name as contestant_name,
				c.contestant_number,
				cat.name as category_name,
				sub.name as subcategory_name,
				j.name as judge_name,
				co.name as contest_name
			FROM scores s
			JOIN contestants c ON s.contestant_id = c.id
			JOIN subcategories sub ON s.subcategory_id = sub.id
			JOIN categories cat ON sub.category_id = cat.id
			JOIN contests co ON cat.contest_id = co.id
			JOIN judges j ON s.judge_id = j.id
			ORDER BY co.name, cat.name, sub.name, c.contestant_number, j.name
		";
		
		$scores = DB::pdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
		
		// Group scores by contest, category, and subcategory
		$groupedScores = [];
		foreach ($scores as $score) {
			$contestName = $score['contest_name'];
			$categoryName = $score['category_name'];
			$subcategoryName = $score['subcategory_name'];
			
			if (!isset($groupedScores[$contestName])) {
				$groupedScores[$contestName] = [];
			}
			if (!isset($groupedScores[$contestName][$categoryName])) {
				$groupedScores[$contestName][$categoryName] = [];
			}
			if (!isset($groupedScores[$contestName][$categoryName][$subcategoryName])) {
				$groupedScores[$contestName][$categoryName][$subcategoryName] = [];
			}
			
			$groupedScores[$contestName][$categoryName][$subcategoryName][] = $score;
		}
		
		view('auditor/scores', compact('groupedScores'));
	}
	
	public function tallyMasterStatus(): void {
		require_auditor();
		
		// Get tally master certification status
		$sql = "
			SELECT 
				u.name as tally_master_name,
				COUNT(DISTINCT jc.id) as total_certifications,
				COUNT(DISTINCT j.id) as total_judges,
				COUNT(DISTINCT CASE WHEN jc.certified_at IS NOT NULL THEN jc.id END) as certified_count
			FROM users u
			LEFT JOIN judge_certifications jc ON u.id = jc.tally_master_id
			LEFT JOIN judges j ON jc.judge_id = j.id
			WHERE u.role = 'tally_master'
			GROUP BY u.id, u.name
		";
		
		$tallyMasterStatus = DB::pdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
		
		// Calculate overall completion status
		$totalJudges = DB::pdo()->query('SELECT COUNT(*) FROM judges')->fetchColumn();
		$totalCertifications = DB::pdo()->query('SELECT COUNT(*) FROM judge_certifications WHERE certified_at IS NOT NULL')->fetchColumn();
		$expectedCertifications = $totalJudges; // Each judge should have one certification
		
		$overallStatus = [
			'total_judges' => $totalJudges,
			'total_certifications' => $totalCertifications,
			'expected_certifications' => $expectedCertifications,
			'completion_percentage' => $expectedCertifications > 0 ? round(($totalCertifications / $expectedCertifications) * 100, 2) : 0,
			'is_complete' => $totalCertifications >= $expectedCertifications
		];
		
		view('auditor/tally-master-status', compact('tallyMasterStatus', 'overallStatus'));
	}
	
	public function finalCertification(): void {
		require_auditor();
		
		// Check if all tally masters have completed their certifications
		$sql = "
			SELECT COUNT(*) as total_judges FROM judges
		";
		$totalJudges = DB::pdo()->query($sql)->fetchColumn();
		
		$sql = "
			SELECT COUNT(*) as certified_count 
			FROM judge_certifications 
			WHERE certified_at IS NOT NULL
		";
		$certifiedCount = DB::pdo()->query($sql)->fetchColumn();
		
		$canCertify = $certifiedCount >= $totalJudges;
		
		if (!$canCertify) {
			redirect('/auditor?error=tally_masters_not_ready');
			return;
		}
		
		// Check if auditor has already certified
		$auditorId = $_SESSION['user']['id'];
		$sql = "
			SELECT COUNT(*) FROM auditor_certifications 
			WHERE auditor_id = ? AND certified_at IS NOT NULL
		";
		$stmt = DB::pdo()->prepare($sql);
		$stmt->execute([$auditorId]);
		$alreadyCertified = $stmt->fetchColumn() > 0;
		
		view('auditor/final-certification', compact('canCertify', 'alreadyCertified'));
	}
	
	public function performFinalCertification(): void {
		require_auditor();
		require_csrf();
		
		// Double-check that all tally masters have certified
		$sql = "
			SELECT COUNT(*) as total_judges FROM judges
		";
		$totalJudges = DB::pdo()->query($sql)->fetchColumn();
		
		$sql = "
			SELECT COUNT(*) as certified_count 
			FROM judge_certifications 
			WHERE certified_at IS NOT NULL
		";
		$certifiedCount = DB::pdo()->query($sql)->fetchColumn();
		
		if ($certifiedCount < $totalJudges) {
			redirect('/auditor/final-certification?error=tally_masters_not_ready');
			return;
		}
		
		$auditorId = $_SESSION['user']['id'];
		$pdo = DB::pdo();
		$pdo->beginTransaction();
		
		try {
			// Create or update auditor certification
			$sql = "
				INSERT OR REPLACE INTO auditor_certifications (auditor_id, certified_at, created_at)
				VALUES (?, datetime('now'), datetime('now'))
			";
			$stmt = $pdo->prepare($sql);
			$stmt->execute([$auditorId]);
			
			$pdo->commit();
			
			redirect('/auditor?success=certification_completed');
		} catch (\Exception $e) {
			$pdo->rollBack();
			redirect('/auditor/final-certification?error=certification_failed');
		}
	}
	
	public function summary(): void {
		require_auditor();
		
		// Get comprehensive score summary
		$sql = "
			SELECT 
				co.name as contest_name,
				cat.name as category_name,
				sub.name as subcategory_name,
				c.name as contestant_name,
				c.contestant_number,
				AVG(s.score) as average_score,
				MIN(s.score) as min_score,
				MAX(s.score) as max_score,
				COUNT(s.score) as score_count
			FROM scores s
			JOIN contestants c ON s.contestant_id = c.id
			JOIN subcategories sub ON s.subcategory_id = sub.id
			JOIN categories cat ON sub.category_id = cat.id
			JOIN contests co ON cat.contest_id = co.id
			GROUP BY co.id, cat.id, sub.id, c.id
			ORDER BY co.name, cat.name, sub.name, c.contestant_number
		";
		
		$summary = DB::pdo()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
		
		// Group by contest and category
		$groupedSummary = [];
		foreach ($summary as $row) {
			$contestName = $row['contest_name'];
			$categoryName = $row['category_name'];
			
			if (!isset($groupedSummary[$contestName])) {
				$groupedSummary[$contestName] = [];
			}
			if (!isset($groupedSummary[$contestName][$categoryName])) {
				$groupedSummary[$contestName][$categoryName] = [];
			}
			
			$groupedSummary[$contestName][$categoryName][] = $row;
		}
		
		view('auditor/summary', compact('groupedSummary'));
	}
}
