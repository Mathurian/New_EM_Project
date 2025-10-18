<h2>Categories for <?= htmlspecialchars($contest['name']) ?></h2>
<p><a href="/contests">Back to Contests</a></p>

<?php if (empty($subcategories)): ?>
	<p>No categories created for this contest yet.</p>
	<p><a href="/contests/<?= urlencode($contest['id']) ?>/categories">Create contests first</a></p>
<?php else: ?>
	<table>
		<tr>
			<th>Contest</th>
			<th>Category</th>
			<th>Description</th>
			<th>Score Cap</th>
			<th>Actions</th>
		</tr>
		<?php foreach ($subcategories as $sc): ?>
			<tr>
				<td><?= htmlspecialchars($sc['category_name']) ?></td>
				<td><?= htmlspecialchars($sc['name']) ?></td>
				<td><?= htmlspecialchars($sc['description'] ?? '') ?></td>
				<td><?= $sc['score_cap'] ? htmlspecialchars($sc['score_cap']) : 'None' ?></td>
				<td>
					<a href="/subcategories/<?= urlencode($sc['id']) ?>/assign">Assign</a> |
					<a href="/subcategories/<?= urlencode($sc['id']) ?>/criteria">Criteria</a> |
					<a href="/score/<?= urlencode($sc['id']) ?>">Score</a> |
					<a href="/results/<?= urlencode($sc['id']) ?>/detailed">Results</a>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
<?php endif; ?>
