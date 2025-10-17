<h2>Edit Template: <?= htmlspecialchars($template['name']) ?></h2>
<p><a href="/admin/templates">Back</a></p>
<form method="post" action="/admin/templates/<?= urlencode($template['id']) ?>/update">
	<div class="form-section">
		<h4>Template Information</h4>
		<div class="form-table">
			<div class="form-row">
				<label class="form-label">Template Name</label>
				<div class="form-input">
					<input type="text" name="name" value="<?= htmlspecialchars($template['name']) ?>" required />
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">Description</label>
				<div class="form-input">
					<textarea name="description" rows="3" cols="60"><?= htmlspecialchars($template['description'] ?? '') ?></textarea>
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">Subcategory Names (one per line)</label>
				<div class="form-input">
					<?php 
					$subcategoryNames = [];
					if (!empty($template['subcategory_names'])) {
						$subcategoryNames = json_decode($template['subcategory_names'], true) ?: [];
					}
					?>
					<textarea name="subcategory_names" rows="5" cols="60" placeholder="Enter subcategory names, one per line&#10;Example:&#10;Technical Skills&#10;Presentation&#10;Creativity"><?= htmlspecialchars(implode("\n", $subcategoryNames)) ?></textarea>
				</div>
			</div>
			
			<div class="form-row">
				<label class="form-label">Max Score per Subcategory</label>
				<div class="form-input">
					<input type="number" name="max_score" min="1" step="1" value="<?= htmlspecialchars($template['max_score'] ?? '60') ?>" required />
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
					<button type="submit" class="btn btn-primary">Update Template</button>
				</div>
			</div>
		</div>
	</div>
</form>
