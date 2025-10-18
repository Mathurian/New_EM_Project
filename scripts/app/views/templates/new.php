<h2>Create Subcategory Template</h2>
<p><a href="/admin/templates">Back</a></p>
<form method="post" action="/admin/templates">
	<label>Template Name
		<input type="text" name="name" required />
	</label>
	<label>Description
		<textarea name="description" rows="3" cols="60"></textarea>
	</label>
	<label>Subcategory Names (one per line)
		<textarea name="subcategory_names" rows="5" cols="60" placeholder="Enter subcategory names, one per line&#10;Example:&#10;Technical Skills&#10;Presentation&#10;Creativity"></textarea>
	</label>
	<label>Max Score per Subcategory
		<input type="number" name="max_score" min="1" step="1" value="60" required />
		<small>Default maximum score for criteria created from this template</small>
	</label>
	<button type="submit">Create Template</button>
</form>
