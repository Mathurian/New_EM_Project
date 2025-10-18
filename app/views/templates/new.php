<h2>Create Category Template</h2>
<p><a href="/admin/templates">Back</a></p>
<form method="post" action="/admin/templates">
	<div class="form-section">
		<h4>Template Information</h4>
		<div class="form-table">
			<div class="form-row">
				<label class="form-label">Template Name</label>
				<div class="form-input">
					<input type="text" name="name" required />
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">Description</label>
				<div class="form-input">
					<textarea name="description" rows="3" cols="60"></textarea>
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">Category Names (one per line)</label>
				<div class="form-input">
					<textarea name="subcategory_names" rows="5" cols="60" placeholder="Enter category names, one per line&#10;Example:&#10;Technical Skills&#10;Presentation&#10;Creativity"></textarea>
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">Max Score per Category</label>
				<div class="form-input">
					<input type="number" name="max_score" min="1" step="1" value="60" required />
					<small>Default maximum score for criteria created from this template</small>
				</div>
			</div>
		</div>
	</div>

	<div class="form-section">
		<div class="form-table">
			<div class="form-row">
				<div class="form-label"></div>
				<div class="form-input">
					<button type="submit" class="btn btn-primary">Create Template</button>
				</div>
			</div>
		</div>
	</div>
</form>
