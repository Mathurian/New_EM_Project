<?php use function App\{url}; ?>
<h2>New Criterion</h2>
<p><a href="<?= url('subcategories/' . urlencode($subcategory['id']) . '/criteria') ?>">Back</a></p>

<form method="post" action="<?= url('subcategories/' . urlencode($subcategory['id']) . '/criteria') ?>">
	<label>Max Score 
		<input type="number" name="max_score" min="1" step="1" value="60" required />
		<small>Maximum score for this criterion</small>
	</label>
	<button type="submit">Create Criterion</button>
</form>


