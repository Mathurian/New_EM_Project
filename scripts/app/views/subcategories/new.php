<h2>New Subcategory</h2>
<p><a href="/categories/<?= urlencode($category['id']) ?>/subcategories">Back</a></p>
<form method="post" action="/categories/<?= urlencode($category['id']) ?>/subcategories">
	<label>Name
		<input name="name" required />
	</label>
	<label>Description
		<textarea name="description" rows="3" cols="60"></textarea>
	</label>
	<label>Score Cap (optional)
		<input type="number" name="score_cap" min="0" step="0.1" />
	</label>
	<button type="submit">Create</button>
</form>


