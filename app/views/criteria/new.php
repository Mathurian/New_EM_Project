<?php use function App\{url}; ?>
<h2>New Criterion</h2>
<p><a href="<?= url('subcategories/' . urlencode($subcategory['id']) . '/criteria') ?>">Back</a></p>

<form method="post" action="<?= url('subcategories/' . urlencode($subcategory['id']) . '/criteria') ?>">
	<div class="form-section">
		<h4>Criterion Information</h4>
		<div class="form-table">
			<div class="form-row">
				<label class="form-label">Max Score</label>
				<div class="form-input">
					<input type="number" name="max_score" min="1" step="1" value="60" required />
					<small>Maximum score for this criterion</small>
				</div>
			</div>
		</div>
	</div>

	<div class="form-section">
		<div class="form-table">
			<div class="form-row">
				<div class="form-label"></div>
				<div class="form-input">
					<button type="submit" class="btn btn-primary">Create Criterion</button>
				</div>
			</div>
		</div>
	</div>
</form>


