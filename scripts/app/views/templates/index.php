<h2>Subcategory Templates</h2>
<p><a href="/admin/templates/new">Create New Template</a></p>
<table>
	<tr>
		<th>Name</th>
		<th>Description</th>
		<th>Subcategories</th>
		<th>Actions</th>
	</tr>
	<?php foreach ($rows as $row): ?>
		<tr>
			<td><?= htmlspecialchars($row['name']) ?></td>
			<td><?= htmlspecialchars($row['description'] ?? '') ?></td>
			<td>
				<?php 
				$subcategoryNames = [];
				if (!empty($row['subcategory_names'])) {
					$subcategoryNames = json_decode($row['subcategory_names'], true) ?: [];
				}
				if (!empty($subcategoryNames)) {
					echo count($subcategoryNames) . ' subcategories';
				} else {
					echo 'None';
				}
				?>
			</td>
			<td>
				<form method="post" action="/admin/templates/<?= urlencode($row['id']) ?>/delete" style="display:inline">
					<button type="submit" onclick="return confirm('Are you sure you want to delete this template?')">Delete</button>
				</form>
			</td>
		</tr>
	<?php endforeach; ?>
</table>
