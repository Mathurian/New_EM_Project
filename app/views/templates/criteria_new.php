<h2>Add Criterion to Template: <?= htmlspecialchars($template['name']) ?></h2>
<p><a href="/admin/templates/<?= urlencode($template['id']) ?>/criteria">Back</a></p>
<form method="post" action="/admin/templates/<?= urlencode($template['id']) ?>/criteria">
	<label>Name (optional)
		<input type="text" name="name" placeholder="Leave blank for auto-generated name" />
	</label>
	<label>Max Score
		<input type="number" name="max_score" min="1" value="60" required />
	</label>
	<button type="submit">Add Criterion</button>
</form>
