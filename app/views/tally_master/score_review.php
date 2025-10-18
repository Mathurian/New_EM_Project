<?php use function App\{url, calculate_score_tabulation, format_score_tabulation}; ?>
<div class="container">
	<h1>Score Review</h1>
	<p>Comprehensive view of all scores across contests, categories, and subcategories.</p>
	
	<?php if (empty($scores)): ?>
		<div class="alert alert-info" style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin: 20px 0;">
			<h4>No Scores Found</h4>
			<p>No scores have been entered yet. Scores will appear here once judges begin scoring contestants.</p>
		</div>
	<?php else: ?>
		<div class="score-summary" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0;">
			<h3>📊 Overall Score Summary</h3>
			<?php 
			$overallTabulation = calculate_score_tabulation($scores);
			?>
			<p><strong>Total Score:</strong> <?= format_score_tabulation($overallTabulation, 'overall') ?></p>
		</div>
		
		<?php foreach ($groupedScores as $key => $groupScores): ?>
			<?php 
			list($contestName, $categoryName, $subcategoryName) = explode('|', $key);
			$subcategoryTabulation = calculate_score_tabulation($groupScores);
			?>
			<div class="subcategory-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0;">
				<h2><?= htmlspecialchars($contestName) ?> 
					<span style="font-size: 0.8em; font-weight: normal; color: #666;">
						(<?= format_score_tabulation($subcategoryTabulation, 'overall') ?>)
					</span>
				</h2>
				<h3><?= htmlspecialchars($categoryName) ?></h3>
				<h4><?= htmlspecialchars($subcategoryName) ?></h4>
				
				<div class="scores-table-container" style="overflow-x: auto;">
					<table class="scores-table" style="width: 100%; border-collapse: collapse; margin: 15px 0;">
						<thead>
							<tr style="background: #f8f9fa;">
								<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Contestant</th>
								<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Judge</th>
								<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Criterion</th>
								<th style="border: 1px solid #dee2e6; padding: 8px; text-align: center;">Score</th>
								<th style="border: 1px solid #dee2e6; padding: 8px; text-align: center;">Max</th>
								<th style="border: 1px solid #dee2e6; padding: 8px; text-align: center;">Certified</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($groupScores as $score): ?>
								<tr>
									<td style="border: 1px solid #dee2e6; padding: 8px;">
										<?= htmlspecialchars($score['contestant_name']) ?>
										<?php if ($score['contestant_number']): ?>
											<br><small>#<?= htmlspecialchars($score['contestant_number']) ?></small>
										<?php endif; ?>
									</td>
									<td style="border: 1px solid #dee2e6; padding: 8px;">
										<?= htmlspecialchars($score['judge_name']) ?>
									</td>
									<td style="border: 1px solid #dee2e6; padding: 8px;">
										<?= htmlspecialchars($score['criterion_name']) ?>
									</td>
									<td style="border: 1px solid #dee2e6; padding: 8px; text-align: center;">
										<strong><?= number_format((float)$score['score'], 1) ?></strong>
									</td>
									<td style="border: 1px solid #dee2e6; padding: 8px; text-align: center;">
										<?= number_format((float)$score['max_score'], 1) ?>
									</td>
									<td style="border: 1px solid #dee2e6; padding: 8px; text-align: center;">
										<?php if ($score['judge_certified']): ?>
											<span style="color: #198754;">✅ <?= htmlspecialchars($score['judge_certified']) ?></span>
											<br><small><?= date('M j, Y g:i A', strtotime($score['judge_certified_at'])) ?></small>
										<?php else: ?>
											<span style="color: #dc3545;">❌ Not Certified</span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		<?php endforeach; ?>
	<?php endif; ?>
	
	<div class="action-buttons" style="margin: 20px 0; text-align: center;">
		<a href="<?= url('tally-master') ?>" class="btn btn-secondary">← Back to Dashboard</a>
		<a href="<?= url('tally-master/certification') ?>" class="btn btn-primary">Manage Certifications</a>
	</div>
</div>
