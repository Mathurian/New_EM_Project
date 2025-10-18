<?php use function App\{url, hierarchical_back_url, home_url}; ?>
<h2>Score Audit</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="score-audit-container">
	<?php foreach ($groupedScores as $contestName => $categories): ?>
		<div class="contest-section">
			<h3><?= htmlspecialchars($contestName) ?></h3>
			
			<?php foreach ($categories as $categoryName => $subcategories): ?>
				<div class="category-section">
					<h4><?= htmlspecialchars($categoryName) ?></h4>
					
					<?php foreach ($subcategories as $subcategoryName => $scores): ?>
						<div class="subcategory-section">
							<h5><?= htmlspecialchars($subcategoryName) ?></h5>
							
							<table class="score-table">
								<thead>
									<tr>
										<th>Contestant</th>
										<th>Number</th>
										<th>Judge</th>
										<th>Score</th>
										<th>Date</th>
										<th>Status</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ($scores as $score): ?>
										<tr>
											<td><?= htmlspecialchars($score['contestant_name']) ?></td>
											<td><?= htmlspecialchars($score['contestant_number'] ?? '-') ?></td>
											<td><?= htmlspecialchars($score['judge_name']) ?></td>
											<td class="score-value"><?= htmlspecialchars($score['score']) ?></td>
											<td><?= date('M j, Y', strtotime($score['created_at'])) ?></td>
											<td>
												<?php if ($score['certified_at']): ?>
													<span class="status-certified">✅ Certified</span>
												<?php else: ?>
													<span class="status-pending">⏳ Pending</span>
												<?php endif; ?>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>
</div>

<style>
.score-audit-container {
	margin: 20px 0;
}

.contest-section {
	margin-bottom: 30px;
	padding: 20px;
	border: 1px solid var(--border-color);
	border-radius: 8px;
	background: var(--bg-primary);
}

.contest-section h3 {
	margin: 0 0 20px 0;
	color: var(--text-primary);
	font-size: 1.5em;
	border-bottom: 2px solid var(--primary-color);
	padding-bottom: 10px;
}

.category-section {
	margin-bottom: 20px;
	padding: 15px;
	border: 1px solid var(--border-color);
	border-radius: 6px;
	background: var(--bg-secondary);
}

.category-section h4 {
	margin: 0 0 15px 0;
	color: var(--text-primary);
	font-size: 1.2em;
}

.subcategory-section {
	margin-bottom: 15px;
	padding: 10px;
	border: 1px solid var(--border-color);
	border-radius: 4px;
	background: var(--bg-tertiary);
}

.subcategory-section h5 {
	margin: 0 0 10px 0;
	color: var(--text-primary);
	font-size: 1.1em;
}

.score-table {
	width: 100%;
	border-collapse: collapse;
	margin-top: 10px;
}

.score-table th,
.score-table td {
	padding: 8px 12px;
	text-align: left;
	border-bottom: 1px solid var(--border-color);
}

.score-table th {
	background: var(--bg-primary);
	font-weight: bold;
	color: var(--text-primary);
}

.score-table td {
	color: var(--text-secondary);
}

.score-value {
	font-weight: bold;
	color: var(--primary-color);
}

.status-certified {
	color: var(--success-color);
	font-weight: bold;
}

.status-pending {
	color: var(--warning-color);
	font-weight: bold;
}
</style>
