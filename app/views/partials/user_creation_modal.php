<?php use function App\{url, hierarchical_back_url, home_url, csrf_field}; use App\DB; ?>
<!-- User Creation Modal/Drawer -->
<div id="user-creation-modal" class="modal-overlay" style="display: none;" role="dialog" aria-labelledby="modal-title" aria-hidden="true">
	<div class="modal-content">
		<div class="modal-header">
			<h3 id="modal-title">Create New User</h3>
			<button type="button" class="modal-close" aria-label="Close modal">&times;</button>
		</div>
		
		<div class="modal-body">
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
					'creation_failed' => 'User creation failed',
					'password_required' => 'Password is required for this role'
				];
				$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
				?>
				<div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
			<?php endif; ?>

			<form method="post" action="<?= url('users') ?>" enctype="multipart/form-data" id="modalUserForm">
				<?= csrf_field() ?>
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
								<small>Required for organizers and tally masters, optional for others</small>
							</div>
						</div>
						
						<div class="form-row">
							<label class="form-label">Password (optional)</label>
							<div class="form-input">
								<input type="password" name="password" placeholder="Leave blank if no login needed" />
								<div class="alert alert-info" style="margin-top:6px;">
									Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.
								</div>
								<small>Required for organizers and tally masters, optional for others</small>
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
								<select name="role" required id="modalRoleSelect">
									<option value="">Select a role</option>
									<option value="organizer">Organizer</option>
									<option value="judge">Judge</option>
									<option value="emcee">Emcee</option>
									<option value="tally_master">Tally Master</option>
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
				<div id="modalContestantFields" style="display: none;" class="form-section" aria-hidden="true">
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
									<?php 
									$categories = DB::pdo()->query('SELECT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id ORDER BY co.name, c.name')->fetchAll(\PDO::FETCH_ASSOC);
									foreach ($categories as $cat): ?>
										<option value="<?= htmlspecialchars($cat['id']) ?>"><?= htmlspecialchars($cat['contest_name']) ?> - <?= htmlspecialchars($cat['name']) ?></option>
									<?php endforeach; ?>
								</select>
								<small>Assign this contestant to a specific contest</small>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Judge-specific fields -->
				<div id="modalJudgeFields" style="display: none;" class="form-section" aria-hidden="true">
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
									<?php foreach ($categories as $cat): ?>
										<option value="<?= htmlspecialchars($cat['id']) ?>"><?= htmlspecialchars($cat['contest_name']) ?> - <?= htmlspecialchars($cat['name']) ?></option>
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
			</form>
		</div>
		
		<div class="modal-footer">
			<button type="submit" form="modalUserForm" class="btn btn-primary">Create User</button>
			<button type="button" class="btn btn-secondary modal-close">Cancel</button>
		</div>
	</div>
</div>

<style>
.modal-overlay {
	position: fixed;
	top: 0;
	left: 0;
	width: 100%;
	height: 100%;
	background: rgba(0, 0, 0, 0.5);
	z-index: 2000;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 20px;
	box-sizing: border-box;
}

.modal-content {
	background: var(--bg-primary);
	border-radius: 8px;
	box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
	max-width: 600px;
	width: 100%;
	max-height: 90vh;
	overflow-y: auto;
	border: 1px solid var(--border-color);
}

.modal-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 20px 20px 0 20px;
	border-bottom: 1px solid var(--border-color);
	margin-bottom: 20px;
}

.modal-header h3 {
	margin: 0;
	color: var(--text-primary);
}

.modal-close {
	background: none;
	border: none;
	font-size: 24px;
	cursor: pointer;
	color: var(--text-secondary);
	padding: 0;
	width: 30px;
	height: 30px;
	display: flex;
	align-items: center;
	justify-content: center;
}

.modal-close:hover {
	color: var(--text-primary);
}

.modal-body {
	padding: 0 20px;
}

.modal-footer {
	padding: 20px;
	border-top: 1px solid var(--border-color);
	display: flex;
	gap: 10px;
	justify-content: flex-end;
}

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

/* Modal footer button spacing */
.modal-footer {
	padding: 20px;
	border-top: 1px solid var(--border-color);
	display: flex;
	gap: 12px;
	justify-content: flex-end;
	align-items: center;
}

.modal-footer button {
	min-width: 100px;
	padding: 10px 16px;
	border-radius: 4px;
	font-weight: 500;
	cursor: pointer;
	transition: all 0.2s;
}

.modal-footer .btn-primary {
	background: var(--accent-color);
	color: white;
	border: 1px solid var(--accent-color);
}

.modal-footer .btn-primary:hover {
	background: var(--accent-color);
	opacity: 0.9;
}

.modal-footer .btn-secondary {
	background: var(--bg-secondary);
	color: var(--text-primary);
	border: 1px solid var(--border-color);
}

.modal-footer .btn-secondary:hover {
	background: var(--bg-tertiary);
	color: var(--text-primary);
}

@media (max-width: 768px) {
	.modal-overlay {
		padding: 10px;
	}
	
	.modal-content {
		max-height: 95vh;
	}
	
	.modal-header,
	.modal-body,
	.modal-footer {
		padding-left: 15px;
		padding-right: 15px;
	}
	
	.modal-footer {
		flex-direction: column;
		gap: 8px;
	}
	
	.modal-footer button {
		width: 100%;
	}
	
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
// Modal functionality
function openUserCreationModal() {
	const modal = document.getElementById('user-creation-modal');
	if (modal) {
		modal.style.display = 'flex';
		modal.setAttribute('aria-hidden', 'false');
		document.body.style.overflow = 'hidden';
		
		// Focus first input
		const firstInput = modal.querySelector('input[name="name"]');
		if (firstInput) {
			setTimeout(() => firstInput.focus(), 100);
		}
	}
}

function closeUserCreationModal() {
	const modal = document.getElementById('user-creation-modal');
	if (modal) {
		modal.style.display = 'none';
		modal.setAttribute('aria-hidden', 'true');
		document.body.style.overflow = '';
		
		// Reset form
		const form = document.getElementById('modalUserForm');
		if (form) {
			form.reset();
			updateModalRoleSections('');
		}
	}
}

function updateModalRoleSections(value) {
	const contestantFields = document.getElementById('modalContestantFields');
	const judgeFields = document.getElementById('modalJudgeFields');
	const map = {
		contestant: contestantFields,
		judge: judgeFields
	};
	
	[contestantFields, judgeFields].forEach(el => { 
		if (el) { 
			el.style.display = 'none'; 
			el.setAttribute('aria-hidden', 'true'); 
		} 
	});
	
	if (map[value]) { 
		map[value].style.display = 'block'; 
		map[value].setAttribute('aria-hidden', 'false'); 
	}
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
	// Close modal buttons
	const closeButtons = document.querySelectorAll('.modal-close');
	closeButtons.forEach(btn => {
		btn.addEventListener('click', closeUserCreationModal);
	});
	
	// Close on overlay click
	const modal = document.getElementById('user-creation-modal');
	if (modal) {
		modal.addEventListener('click', function(e) {
			if (e.target === modal) {
				closeUserCreationModal();
			}
		});
	}
	
	// Close on escape key
	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape') {
			const modal = document.getElementById('user-creation-modal');
			if (modal && modal.style.display !== 'none') {
				closeUserCreationModal();
			}
		}
	});
	
	// Role selection handler
	const roleSelect = document.getElementById('modalRoleSelect');
	if (roleSelect) {
		roleSelect.addEventListener('change', function() {
			updateModalRoleSections(this.value);
		});
	}
	
	// Initialize role sections
	updateModalRoleSections('');
});

// Make functions globally available
window.openUserCreationModal = openUserCreationModal;
window.closeUserCreationModal = closeUserCreationModal;
</script>
