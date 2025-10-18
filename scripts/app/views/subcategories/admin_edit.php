<h2>Edit Subcategory: <?= htmlspecialchars($subcategory['category_name']) ?> - <?= htmlspecialchars($subcategory['name']) ?></h2>
<p><a href="/categories/<?= htmlspecialchars($subcategory['category_id']) ?>/subcategories">Back</a></p>
<form method="post" action="/subcategories/<?= htmlspecialchars($subcategory['id']) ?>/admin">
	<input type="hidden" name="category_id" value="<?= htmlspecialchars($subcategory['category_id']) ?>" />
	<label>Score Cap (max per criterion)
		<input type="number" name="score_cap" min="0" step="1" value="<?= htmlspecialchars((string)($subcategory['score_cap'] ?? '')) ?>" />
	</label>
	<button type="submit">Save</button>
</form>


