<?php use function App\{url}; ?>
<!DOCTYPE html>
<html>
<head>
	<title>Contestant Scores - <?= htmlspecialchars($contestant['name']) ?></title>
	<style>
		body { font-family: Arial, sans-serif; margin: 20px; }
		.print-only { display: none; }
		@media print {
			.no-print { display: none !important; }
			.print-only { display: block !important; }
			body { margin: 0; }
			.page-break { page-break-before: always; }
		}
		.header { text-align: center; margin-bottom: 30px; }
		.contestant-info { background: #f5f5f5; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
		.scores-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
		.scores-table th, .scores-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
		.scores-table th { background-color: #f2f2f2; font-weight: bold; }
		.comments-section { margin-top: 20px; }
		.comment-box { background: #f9f9f9; padding: 10px; margin: 10px 0; border-left: 4px solid #007cba; }
		.total-score { font-weight: bold; font-size: 1.2em; }
		.subcategory-header { background: #007cba; color: white; padding: 10px; margin: 20px 0 10px 0; }
	</style>
</head>
<body>
	<div class="no-print">
		<p><a href="<?= url('admin/users') ?>">Back to User Management</a></p>
		<button onclick="window.print()" style="background: #007cba; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Print Scores</button>
	</div>

	<div class="header">
		<h1>Contestant Score Report</h1>
		<div class="contestant-info">
			<h2><?= htmlspecialchars($contestant['name']) ?></h2>
			<?php if (!empty($contestant['contestant_number'])): ?>
				<p><strong>Contestant Number:</strong> <?= htmlspecialchars($contestant['contestant_number']) ?></p>
			<?php endif; ?>
			<?php if (!empty($contestant['email'])): ?>
				<p><strong>Email:</strong> <?= htmlspecialchars($contestant['email']) ?></p>
			<?php endif; ?>
			<?php if (!empty($contestant['gender'])): ?>
				<p><strong>Gender:</strong> <?= htmlspecialchars($contestant['gender']) ?></p>
			<?php endif; ?>
			<p><strong>Total Score:</strong> <?= format_score_tabulation($tabulation, 'overall') ?></p>
			<p><strong>Report Generated:</strong> <?= date('Y-m-d H:i:s') ?></p>
		</div>
	</div>

	<?php
	// Group scores by subcategory
	$scoresBySubcategory = [];
	foreach ($scores as $score) {
		$key = $score['category_name'] . ' - ' . $score['subcategory_name'];
		$scoresBySubcategory[$key][] = $score;
	}
	
	// Group comments by subcategory
	$commentsBySubcategory = [];
	foreach ($comments as $comment) {
		$key = $comment['category_name'] . ' - ' . $comment['subcategory_name'];
		$commentsBySubcategory[$key][] = $comment;
	}
	?>

	<?php foreach ($scoresBySubcategory as $subcategoryName => $subcategoryScores): ?>
		<div class="subcategory-header">
			<h3><?= htmlspecialchars($subcategoryName) ?></h3>
		</div>
		
		<table class="scores-table">
			<tr>
				<th>Criterion</th>
				<th>Max Score</th>
				<th>Judge</th>
				<th>Score</th>
				<th>Percentage</th>
			</tr>
			<?php 
			$subcategoryTotal = 0;
			$subcategoryMaxTotal = 0;
			foreach ($subcategoryScores as $score): 
				$percentage = $score['max_score'] > 0 ? round(($score['score'] / $score['max_score']) * 100, 1) : 0;
				$subcategoryTotal += $score['score'];
				$subcategoryMaxTotal += $score['max_score'];
			?>
				<tr>
					<td><?= htmlspecialchars($score['criterion_name']) ?></td>
					<td><?= htmlspecialchars($score['max_score']) ?></td>
					<td><?= htmlspecialchars($score['judge_name']) ?></td>
					<td><?= htmlspecialchars($score['score']) ?></td>
					<td><?= $percentage ?>%</td>
				</tr>
			<?php endforeach; ?>
			<tr class="total-score">
				<td colspan="3"><strong>Subcategory Total</strong></td>
				<td><strong><?= $subcategoryTotal ?></strong></td>
				<td><strong><?= $subcategoryMaxTotal > 0 ? round(($subcategoryTotal / $subcategoryMaxTotal) * 100, 1) : 0 ?>%</strong></td>
			</tr>
		</table>

		<?php if (!empty($commentsBySubcategory[$subcategoryName])): ?>
			<div class="comments-section">
				<h4>Judge Comments</h4>
				<?php foreach ($commentsBySubcategory[$subcategoryName] as $comment): ?>
					<div class="comment-box">
						<strong><?= htmlspecialchars($comment['judge_name']) ?>:</strong><br>
						<?= nl2br(htmlspecialchars($comment['comment'])) ?>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		
		<div class="page-break"></div>
	<?php endforeach; ?>

	<?php if (empty($scores)): ?>
		<p>No scores recorded for this contestant yet.</p>
	<?php endif; ?>

	<div class="print-only">
		<p style="text-align: center; margin-top: 50px; font-size: 12px; color: #666;">
			Report generated on <?= date('Y-m-d H:i:s') ?> | Contest Management System
		</p>
	</div>
</body>
</html>
