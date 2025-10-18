<?php use function App\{url, hierarchical_back_url, home_url}; ?>
<h2>New Contest</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<form method="post" action="/contests" class="card">
	<div class="form-group">
		<label for="name">Contest Name</label>
		<input type="text" id="name" name="name" required />
	</div>
	
	<div class="form-group">
		<label for="start_date">Start Date</label>
		<input type="date" id="start_date" name="start_date" required />
	</div>
	
	<div class="form-group">
		<label for="end_date">End Date</label>
		<input type="date" id="end_date" name="end_date" required />
	</div>
	
	<div class="form-group">
		<button type="submit" class="btn btn-primary">Create Contest</button>
	</div>
</form>


