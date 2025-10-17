<?php use function App\{url, hierarchical_back_url, home_url}; use App\DB; ?>
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
					'creation_failed' => 'User creation failed'
				];
				$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
				?>
				<div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
			<?php endif; ?>

			<form method="post" action="<?= url('users') ?>" enctype="multipart/form-data" id="modalUserForm">
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
						<select name="role" required id="modalRoleSelect">
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
				<div id="modalContestantFields" style="display: none;" class="form-section" aria-hidden="true">
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
							<?php 
							$categories = DB::pdo()->query('SELECT c.*, co.name as contest_name FROM categories c JOIN contests co ON c.contest_id = co.id ORDER BY co.name, c.name')->fetchAll(\PDO::FETCH_ASSOC);
							foreach ($categories as $category): ?>
								<option value="<?= htmlspecialchars($category['id']) ?>"><?= htmlspecialchars($category['contest_name']) ?> - <?= htmlspecialchars($category['name']) ?></option>
							<?php endforeach; ?>
						</select>
						<small>Assign this contestant to a specific category</small>
					</label>
				</div>
				
				<!-- Judge-specific fields -->
				<div id="modalJudgeFields" style="display: none;" class="form-section" aria-hidden="true">
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
	}
	
	.modal-footer button {
		width: 100%;
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
