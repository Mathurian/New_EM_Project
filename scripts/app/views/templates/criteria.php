<h2>Template Criteria: <?= htmlspecialchars($template['name']) ?></h2>
<p><a href="/admin/templates">Back to Templates</a></p>
<p><a href="/admin/templates/<?= urlencode($template['id']) ?>/criteria/new">Add Criterion</a></p>
<table>
	<tr>
		<th>Name</th>
		<th>Max Score</th>
		<th>Actions</th>
	</tr>
	<?php foreach ($rows as $row): ?>
		<tr>
			<td><?= htmlspecialchars($row['name']) ?></td>
			<td><?= htmlspecialchars($row['max_score']) ?></td>
			<td>
				<!-- Add delete functionality if needed -->
			</td>
		</tr>
	<?php endforeach; ?>
</table>
