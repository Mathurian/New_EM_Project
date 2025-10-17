<?php use function App\{url, hierarchical_back_url, home_url}; ?>
<h2>Create New User</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="card">
	<h3>User Creation Options</h3>
	<div style="display: flex; gap: 12px; margin-bottom: 16px;">
		<button type="button" class="btn btn-primary" onclick="openUserCreationModal()">
			👤 Create User (Modal)
		</button>
		<button type="button" class="btn btn-secondary" onclick="toggleInlineForm()">
			📝 Create User (Inline)
		</button>
	</div>
	<p class="text-muted">Choose between modal or inline form for user creation.</p>
</div>

<!-- Inline Form -->
<div id="inline-form" class="card" style="display: none;">
	<?php if (!empty($_GET['error'])): ?>
		<?php 
		$errorMessages = [
			'invalid_role' => 'Invalid role selected',
			'email_exists' => 'Email already exists',
			'preferred_name_exists' => 'Preferred name already exists',
			'constraint_failed' => 'Database constraint error. Please run the constraint fix script.',
			'database_error' => 'Database error occurred',
			'upload_failed' => 'Failed to upload image',
			'missing_fields' => 'Required fields are missing',
			'creation_failed' => 'User creation failed'
		];
		$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
		?>
		<div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
		<?php if ($_GET['error'] === 'constraint_failed'): ?>
			<div class="alert alert-warning">
				<strong>Fix:</strong> Run <code>php fix_constraint.php</code> from the project root directory to update the database constraint.
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<form method="post" action="<?= url('users') ?>" enctype="multipart/form-data" id="userForm">
		<div class="form-section">
			<h4>Basic Information</h4>
			<label>Full Name
				<input type="text" name="name" required />
			</label>
			
			<label>Preferred Name (optional)
				<input type="text" name="preferred_name" placeholder="Leave blank to use full name" />
				<small>This can be used for login and signatures</small>
			</label>
			
			<label>Email Address (optional)
				<input type="email" name="email" placeholder="Leave blank if no email needed" />
				<small>Required for organizers, optional for others</small>
			</label>
			
			<label>Password (optional)
				<input type="password" name="password" placeholder="Leave blank if no login needed" />
				<div class="alert alert-info" style="margin-top:6px;">
					Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.
				</div>
				<small>Required for organizers, optional for others</small>
			</label>
		</div>

		<div class="form-section">
			<h4>Role & Details</h4>
			<label>Role
				<select name="role" required id="roleSelect">
					<option value="">Select a role</option>
					<option value="organizer">Organizer</option>
					<option value="judge">Judge</option>
					<option value="emcee">Emcee</option>
					<option value="contestant">Contestant</option>
				</select>
			</label>
			
			<label>Gender (optional)
				<input type="text" name="gender" placeholder="Enter custom gender or leave blank" />
			</label>
			
			<label>Pronouns (optional)
				<input type="text" name="pronouns" placeholder="e.g., they/them, she/her, he/him, ze/zir, or custom" />
				<small>How you would like to be referred to</small>
			</label>
		</div>

		<!-- Contestant-specific fields -->
		<div id="contestantFields" style="display: none;" class="form-section" aria-hidden="true">
			<h4>Contestant Information</h4>
			
			<label>Contestant Number (optional)
				<input type="number" name="contestant_number" min="1" placeholder="Leave blank for auto-assignment" />
				<small>Will be automatically assigned if not provided</small>
			</label>
			
			<label>Bio
				<textarea name="bio" rows="4" cols="50" placeholder="Tell us about yourself, your background, and what makes you unique..."></textarea>
			</label>
			
			<label>Profile Image
				<input type="file" name="image" accept="image/*" />
				<small>Upload a professional photo for your contestant profile</small>
			</label>
			
			<label>Assign to Category (optional)
				<select name="category_id">
					<option value="">No category assignment</option>
					<?php foreach ($categories as $category): ?>
						<option value="<?= htmlspecialchars($category['id']) ?>"><?= htmlspecialchars($category['contest_name']) ?> - <?= htmlspecialchars($category['name']) ?></option>
					<?php endforeach; ?>
				</select>
				<small>Assign this contestant to a specific category</small>
			</label>
		</div>
		
		<!-- Judge-specific fields -->
		<div id="judgeFields" style="display: none;" class="form-section" aria-hidden="true">
			<h4>Judge Information</h4>
			
			<label>Bio
				<textarea name="bio" rows="4" cols="50" placeholder="Tell us about your experience, qualifications, and what makes you a great judge..."></textarea>
			</label>
			
			<label>Profile Image
				<input type="file" name="image" accept="image/*" />
				<small>Upload a professional photo for your judge profile</small>
			</label>
			
			<label>Assign to Category (optional)
				<select name="category_id">
					<option value="">No category assignment</option>
					<?php foreach ($categories as $category): ?>
						<option value="<?= htmlspecialchars($category['id']) ?>"><?= htmlspecialchars($category['contest_name']) ?> - <?= htmlspecialchars($category['name']) ?></option>
					<?php endforeach; ?>
				</select>
				<small>Assign this judge to a specific category</small>
			</label>
			<label><input type="checkbox" name="is_head_judge" value="1" /> Head Judge</label>
		</div>
		
		<div style="margin-top:12px; display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
			<button type="submit" class="btn btn-primary">Create User</button>
			<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">Cancel</a>
		</div>
	</form>
</div>

<style>
.form-section {
	margin-bottom: 24px;
}

.form-section h4 {
	margin: 0 0 16px 0;
	color: var(--text-primary);
	font-size: 1.1em;
	border-bottom: 1px solid var(--border-color);
	padding-bottom: 8px;
}
</style>

<script>
function toggleInlineForm() {
	const form = document.getElementById('inline-form');
	if (form) {
		form.style.display = form.style.display === 'none' ? 'block' : 'none';
	}
}

function updateRoleSections(value){
	const contestantFields = document.getElementById('contestantFields');
	const judgeFields = document.getElementById('judgeFields');
	const map = {
		contestant: contestantFields,
		judge: judgeFields
	};
	[contestantFields, judgeFields].forEach(el => { if (el) { el.style.display = 'none'; el.setAttribute('aria-hidden','true'); } });
	if (map[value]) { map[value].style.display = 'block'; map[value].setAttribute('aria-hidden','false'); }
}
document.getElementById('roleSelect').addEventListener('change', function(){ updateRoleSections(this.value); });
// Initialize on load (handles back navigation with preselected value)
document.addEventListener('DOMContentLoaded', function(){
	const sel = document.getElementById('roleSelect');
	if (sel) updateRoleSections(sel.value);
});
</script>
