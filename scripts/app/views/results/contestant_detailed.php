<h2>Detailed Scores: <?= htmlspecialchars($contestant['name']) ?> - <?= htmlspecialchars($category['name']) ?></h2>
<p><a href="/results">Back to Results</a></p>

<h3>Contestant Information</h3>
<p><strong>Name:</strong> <?= htmlspecialchars($contestant['name']) ?></p>
<?php if (!empty($contestant['email'])): ?>
	<p><strong>Email:</strong> <?= htmlspecialchars($contestant['email']) ?></p>
<?php endif; ?>
<?php if (!empty($contestant['gender'])): ?>
	<p><strong>Gender:</strong> <?= htmlspecialchars($contestant['gender']) ?></p>
<?php endif; ?>

<h3>Scores by Subcategory</h3>
<?php if (empty($scores)): ?>
	<p>No scores recorded for this contestant in this category.</p>
<?php else: ?>
	<?php
	// Group scores by subcategory
	$scoresBySubcategory = [];
	foreach ($scores as $score) {
		$scoresBySubcategory[$score['subcategory_name']][] = $score;
	}
	?>
	
	<?php foreach ($scoresBySubcategory as $subcategoryName => $subcategoryScores): ?>
		<h4><?= htmlspecialchars($subcategoryName) ?></h4>
		<table border="1" cellpadding="5" cellspacing="0">
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
				$percentage = $score['max_score'] > 0 ? ($score['score'] / $score['max_score']) * 100 : 0;
				$subcategoryTotal += $score['score'];
				$subcategoryMaxTotal += $score['max_score'];
			?>
				<tr>
					<td><?= htmlspecialchars($score['criterion_name']) ?></td>
					<td><?= htmlspecialchars($score['max_score']) ?></td>
					<td><?= htmlspecialchars($score['judge_name']) ?></td>
					<td><?= htmlspecialchars($score['score']) ?></td>
					<td><?= number_format($percentage, 1) ?>%</td>
				</tr>
			<?php endforeach; ?>
			<tr style="font-weight: bold; background-color: #f0f0f0;">
				<td colspan="3">Subcategory Total</td>
				<td><?= number_format($subcategoryTotal, 2) ?></td>
				<td><?= $subcategoryMaxTotal > 0 ? number_format(($subcategoryTotal / $subcategoryMaxTotal) * 100, 1) : 0 ?>%</td>
			</tr>
		</table>
	<?php endforeach; ?>
<?php endif; ?>

<h3>Judge Comments</h3>
<?php if (empty($comments)): ?>
	<p>No comments recorded for this contestant in this category.</p>
<?php else: ?>
	<?php foreach ($comments as $comment): ?>
		<div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">
			<strong><?= htmlspecialchars($comment['judge_name']) ?> - <?= htmlspecialchars($comment['subcategory_name']) ?></strong><br>
			<?= htmlspecialchars($comment['comment']) ?>
		</div>
	<?php endforeach; ?>
<?php endif; ?>
