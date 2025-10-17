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
			<div class="form-table">
				<div class="form-row">
					<label class="form-label">Full Name</label>
					<div class="form-input">
						<input type="text" name="name" required />
					</div>
				</div>
				
				<div class="form-row">
					<label class="form-label">Preferred Name (optional)</label>
					<div class="form-input">
						<input type="text" name="preferred_name" placeholder="Leave blank to use full name" />
						<small>This can be used for login and signatures</small>
					</div>
				</div>
				
				<div class="form-row">
					<label class="form-label">Email Address (optional)</label>
					<div class="form-input">
						<input type="email" name="email" placeholder="Leave blank if no email needed" />
						<small>Required for organizers, optional for others</small>
					</div>
				</div>
				
				<div class="form-row">
					<label class="form-label">Password (optional)</label>
					<div class="form-input">
						<input type="password" name="password" placeholder="Leave blank if no login needed" />
						<div class="alert alert-info" style="margin-top:6px;">
							Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.
						</div>
						<small>Required for organizers, optional for others</small>
					</div>
				</div>
			</div>
		</div>

		<div class="form-section">
			<h4>Role & Details</h4>
			<div class="form-table">
				<div class="form-row">
					<label class="form-label">Role</label>
					<div class="form-input">
						<select name="role" required id="roleSelect">
							<option value="">Select a role</option>
							<option value="organizer">Organizer</option>
							<option value="judge">Judge</option>
							<option value="emcee">Emcee</option>
							<option value="contestant">Contestant</option>
						</select>
					</div>
				</div>
				
				<div class="form-row">
					<label class="form-label">Gender (optional)</label>
					<div class="form-input">
						<input type="text" name="gender" placeholder="Enter custom gender or leave blank" />
					</div>
				</div>
				
				<div class="form-row">
					<label class="form-label">Pronouns (optional)</label>
					<div class="form-input">
						<input type="text" name="pronouns" placeholder="e.g., they/them, she/her, he/him, ze/zir, or custom" />
						<small>How you would like to be referred to</small>
					</div>
				</div>
			</div>
		</div>

		<!-- Contestant-specific fields -->
		<div id="contestantFields" style="display: none;" class="form-section" aria-hidden="true">
			<h4>Contestant Information</h4>
			<div class="form-table">
				<div class="form-row">
					<label class="form-label">Contestant Number (optional)</label>
					<div class="form-input">
						<input type="number" name="contestant_number" min="1" placeholder="Leave blank for auto-assignment" />
						<small>Will be automatically assigned if not provided</small>
					</div>
				</div>
				
				<div class="form-row">
					<label class="form-label">Bio</label>
					<div class="form-input">
						<textarea name="bio" rows="4" cols="50" placeholder="Tell us about yourself, your background, and what makes you unique..."></textarea>
					</div>
				</div>
				
				<div class="form-row">
					<label class="form-label">Profile Image</label>
					<div class="form-input">
						<input type="file" name="image" accept="image/*" />
						<small>Upload a professional photo for your contestant profile</small>
					</div>
				</div>
				
				<div class="form-row">
					<label class="form-label">Assign to Contest (optional)</label>
					<div class="form-input">
						<select name="category_id">
							<option value="">No contest assignment</option>
							<?php foreach ($categories as $category): ?>
								<option value="<?= htmlspecialchars($category['id']) ?>"><?= htmlspecialchars($category['contest_name']) ?> - <?= htmlspecialchars($category['name']) ?></option>
							<?php endforeach; ?>
						</select>
						<small>Assign this contestant to a specific contest</small>
					</div>
				</div>
			</div>
		</div>
		
		<!-- Judge-specific fields -->
		<div id="judgeFields" style="display: none;" class="form-section" aria-hidden="true">
			<h4>Judge Information</h4>
			<div class="form-table">
				<div class="form-row">
					<label class="form-label">Bio</label>
					<div class="form-input">
						<textarea name="bio" rows="4" cols="50" placeholder="Tell us about your experience, qualifications, and what makes you a great judge..."></textarea>
					</div>
				</div>
				
				<div class="form-row">
					<label class="form-label">Profile Image</label>
					<div class="form-input">
						<input type="file" name="image" accept="image/*" />
						<small>Upload a professional photo for your judge profile</small>
					</div>
				</div>
				
				<div class="form-row">
					<label class="form-label">Assign to Contest (optional)</label>
					<div class="form-input">
						<select name="category_id">
							<option value="">No contest assignment</option>
							<?php foreach ($categories as $category): ?>
								<option value="<?= htmlspecialchars($category['id']) ?>"><?= htmlspecialchars($category['contest_name']) ?> - <?= htmlspecialchars($category['name']) ?></option>
							<?php endforeach; ?>
						</select>
						<small>Assign this judge to a specific contest</small>
					</div>
				</div>
				
				<div class="form-row">
					<label class="form-label">Head Judge</label>
					<div class="form-input">
						<label><input type="checkbox" name="is_head_judge" value="1" /> Head Judge</label>
					</div>
				</div>
			</div>
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

/* Table-like form layout */
.form-table {
	display: table;
	width: 100%;
	border-collapse: separate;
	border-spacing: 0;
}

.form-row {
	display: table-row;
}

.form-label {
	display: table-cell;
	width: 35%;
	padding: 12px 16px 12px 0;
	vertical-align: top;
	font-weight: 500;
	color: var(--text-primary);
	text-align: right;
}

.form-input {
	display: table-cell;
	width: 65%;
	padding: 12px 0;
	vertical-align: top;
}

.form-input input,
.form-input select,
.form-input textarea {
	width: 100%;
	padding: 8px 12px;
	border: 1px solid var(--border-color);
	border-radius: 4px;
	font-size: 14px;
	background: var(--bg-primary);
	color: var(--text-primary);
}

.form-input input:focus,
.form-input select:focus,
.form-input textarea:focus {
	outline: none;
	border-color: var(--accent-color);
	box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
}

.form-input small {
	display: block;
	margin-top: 4px;
	color: var(--text-secondary);
	font-size: 12px;
}

.form-input label {
	font-weight: normal;
	margin: 0;
}

.form-input input[type="checkbox"] {
	width: auto;
	margin-right: 8px;
}

.form-input input[type="file"] {
	padding: 4px 8px;
}

@media (max-width: 768px) {
	/* Mobile table layout - stack vertically */
	.form-table {
		display: block;
	}
	
	.form-row {
		display: block;
		margin-bottom: 16px;
	}
	
	.form-label {
		display: block;
		width: 100%;
		padding: 0 0 6px 0;
		text-align: left;
		font-weight: 500;
	}
	
	.form-input {
		display: block;
		width: 100%;
		padding: 0;
	}
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
