<h2>New Subcategory</h2>
<p><a href="/categories/<?= urlencode($category['id']) ?>/subcategories">Back</a></p>
<form method="post" action="/categories/<?= urlencode($category['id']) ?>/subcategories">
	<div class="form-section">
		<h4>Subcategory Information</h4>
		<div class="form-table">
			<div class="form-row">
				<label class="form-label">Name</label>
				<div class="form-input">
					<input name="name" required />
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">Description</label>
				<div class="form-input">
					<textarea name="description" rows="3" cols="60"></textarea>
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">Score Cap (optional)</label>
				<div class="form-input">
					<input type="number" name="score_cap" min="0" step="0.1" />
				</div>
			</div>
		</div>
	</div>

	<div class="form-section">
		<div class="form-table">
			<div class="form-row">
				<div class="form-label"></div>
				<div class="form-input">
					<button type="submit" class="btn btn-primary">Create</button>
				</div>
			</div>
		</div>
	</div>
</form>


