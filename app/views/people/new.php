<?php use function App\{url, hierarchical_back_url, home_url}; ?>
<h2>Add People</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="card">
	<h3>Quick Add Options</h3>
	<div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 20px;">
		<button type="button" class="btn btn-primary" onclick="openUserCreationModal()">
			👤 Create Full User Account
		</button>
		<button type="button" class="btn btn-secondary" onclick="toggleQuickAdd('contestant')">
			🏆 Quick Add Contestant
		</button>
		<button type="button" class="btn btn-secondary" onclick="toggleQuickAdd('judge')">
			⚖️ Quick Add Judge
		</button>
	</div>
	<p class="text-muted">Use "Create Full User Account" for complete user management, or quick add for simple entries.</p>
</div>

<!-- Quick Add Contestant -->
<div id="quick-add-contestant" class="card" style="display: none;">
	<h4>Quick Add Contestant</h4>
	<form method="post" action="/contestants" enctype="multipart/form-data">
		<div class="form-row">
			<label>Name
				<input name="name" required />
			</label>
			<label>Email
				<input name="email" type="email" />
			</label>
		</div>
		<div class="form-row">
			<label>Gender (optional)
				<input name="gender" placeholder="Enter custom gender or leave blank" />
			</label>
			<label>Pronouns (optional)
				<input name="pronouns" placeholder="e.g., they/them, she/her, he/him" />
			</label>
		</div>
		<div class="form-row">
			<label>Contestant Number
				<input type="number" name="contestant_number" min="1" placeholder="Auto-assigned if blank" />
			</label>
		</div>
		<label>Bio
			<textarea name="bio" rows="3" placeholder="Tell us about yourself..."></textarea>
		</label>
		<label>Profile Image
			<input type="file" name="image" accept="image/*" />
		</label>
		<div style="margin-top: 12px; display: flex; gap: 10px;">
			<button type="submit" class="btn btn-primary">Add Contestant</button>
			<button type="button" class="btn btn-secondary" onclick="toggleQuickAdd('contestant')">Cancel</button>
		</div>
	</form>
</div>

<!-- Quick Add Judge -->
<div id="quick-add-judge" class="card" style="display: none;">
	<h4>Quick Add Judge</h4>
	<form method="post" action="/judges" enctype="multipart/form-data">
		<div class="form-row">
			<label>Name
				<input name="name" required />
			</label>
			<label>Email
				<input name="email" type="email" />
			</label>
		</div>
		<div class="form-row">
			<label>Gender (optional)
				<input name="gender" placeholder="Enter custom gender or leave blank" />
			</label>
			<label>Pronouns (optional)
				<input name="pronouns" placeholder="e.g., they/them, she/her, he/him" />
			</label>
		</div>
		<label>Bio
			<textarea name="bio" rows="3" placeholder="Tell us about your experience..."></textarea>
		</label>
		<label>Profile Image
			<input type="file" name="image" accept="image/*" />
		</label>
		<div style="margin-top: 12px; display: flex; gap: 10px;">
			<button type="submit" class="btn btn-primary">Add Judge</button>
			<button type="button" class="btn btn-secondary" onclick="toggleQuickAdd('judge')">Cancel</button>
		</div>
	</form>
</div>

<style>
.form-row {
	display: flex;
	gap: 12px;
	margin-bottom: 12px;
}

.form-row label {
	flex: 1;
}

@media (max-width: 768px) {
	.form-row {
		flex-direction: column;
		gap: 8px;
	}
}
</style>

<script>
function toggleQuickAdd(type) {
	const contestantDiv = document.getElementById('quick-add-contestant');
	const judgeDiv = document.getElementById('quick-add-judge');
	
	// Hide all first
	contestantDiv.style.display = 'none';
	judgeDiv.style.display = 'none';
	
	// Show selected
	if (type === 'contestant') {
		contestantDiv.style.display = contestantDiv.style.display === 'none' ? 'block' : 'none';
	} else if (type === 'judge') {
		judgeDiv.style.display = judgeDiv.style.display === 'none' ? 'block' : 'none';
	}
}
</script>


