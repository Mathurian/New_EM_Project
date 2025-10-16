<h2>Edit Template: <?= htmlspecialchars($template['name']) ?></h2>
<p><a href="/admin/templates">Back</a></p>
<form method="post" action="/admin/templates/<?= urlencode($template['id']) ?>/update">
	<label>Template Name
		<input type="text" name="name" value="<?= htmlspecialchars($template['name']) ?>" required />
	</label>
	<label>Description
		<textarea name="description" rows="3" cols="60"><?= htmlspecialchars($template['description'] ?? '') ?></textarea>
	</label>
	<label>Subcategory Names (one per line)
		<?php 
		$subcategoryNames = [];
		if (!empty($template['subcategory_names'])) {
			$subcategoryNames = json_decode($template['subcategory_names'], true) ?: [];
		}
		?>
		<textarea name="subcategory_names" rows="5" cols="60" placeholder="Enter subcategory names, one per line&#10;Example:&#10;Technical Skills&#10;Presentation&#10;Creativity"><?= htmlspecialchars(implode("\n", $subcategoryNames)) ?></textarea>
	</label>
	<label>Max Score per Subcategory
		<input type="number" name="max_score" min="1" step="1" value="<?= htmlspecialchars($template['max_score'] ?? '60') ?>" required />
		<small>Default maximum score for criteria created from this template</small>
	</label>
	<button type="submit">Update Template</button>
</form>
