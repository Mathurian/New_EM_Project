<?php use function App\{url, is_organizer, hierarchical_back_url, home_url}; ?>
<h2>User Management</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<?php if (is_organizer()): ?>
	<div class="warning-box">
		<h3>Admin Actions</h3>
		<p><strong>Unsign Scores:</strong> Go to any scoring page to unsign certified scores for judges.</p>
		<p><strong>Bulk User Removal:</strong> Use the buttons below to remove all users of a specific type (WARNING: This will delete all associated data, bios, and images):</p>
		
		<div class="admin-buttons">
			<form method="post" action="<?= url('admin/users/remove-all-judges') ?>" style="display: inline-block; margin-right: 10px;">
				<?= csrf_field() ?>
				<button type="submit" onclick="return confirm('Are you sure you want to remove ALL judges? This will delete all scores, comments, certifications, and associated images. This action cannot be undone!')" class="btn btn-danger">
					Remove All Judges
				</button>
			</form>
			
			<form method="post" action="<?= url('admin/users/remove-all-contestants') ?>" style="display: inline-block; margin-right: 10px;">
				<?= csrf_field() ?>
				<button type="submit" onclick="return confirm('Are you sure you want to remove ALL contestants? This will delete all scores, comments, bios, and associated images. This action cannot be undone!')" class="btn btn-danger">
					Remove All Contestants
				</button>
			</form>
			
			<form method="post" action="<?= url('admin/users/remove-all-emcees') ?>" style="display: inline-block;">
				<?= csrf_field() ?>
				<button type="submit" onclick="return confirm('Are you sure you want to remove ALL emcees? This action cannot be undone!')" class="btn btn-danger">
					Remove All Emcees
				</button>
			</form>
			
			<form method="post" action="<?= url('admin/users/force-refresh') ?>" style="display: inline-block; margin-right: 10px; vertical-align: middle;">
				<?= csrf_field() ?>
				<button type="submit" class="btn btn-primary">
					Force Refresh Tables
				</button>
			</form>
			<form method="post" action="<?= url('admin/users/force-logout-all') ?>" style="display: inline-block; margin-right: 10px; vertical-align: middle;">
				<?= csrf_field() ?>
				<button type="submit" class="btn btn-primary" onclick="return confirm('Force logout all users? Current sessions will be invalidated.')">Force Logout All Users</button>
			</form>
			<a href="<?= url('users/new') ?>" class="btn btn-primary" style="display:inline-block; margin-left: 10px; vertical-align: middle;">Create User</a>
		</div>
	</div>
<?php endif; ?>

<?php if (!empty($_GET['success'])): ?>
	<?php 
    $successMessages = [
		'user_created' => 'User created successfully!',
		'user_updated' => 'User updated successfully!',
		'user_deleted' => 'User deleted successfully!',
		'all_judges_removed' => 'All judges and associated data have been removed successfully!',
		'all_contestants_removed' => 'All contestants and associated data have been removed successfully!',
        'all_emcees_removed' => 'All emcees have been removed successfully!',
        'table_refreshed' => 'User tables have been refreshed successfully!',
        'forced_logout_user' => 'User has been forced to log out.',
        'forced_logout_all' => 'All users have been forced to log out.'
	];
	$successMessage = $successMessages[$_GET['success']] ?? 'Operation completed successfully!';
	?>
	<p style="color: green; font-weight: bold;"><?= htmlspecialchars($successMessage) ?></p>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
	<?php 
	$errorMessages = [
		'user_not_found' => 'User not found',
		'invalid_role' => 'Invalid role selected',
		'email_exists' => 'Email already exists',
		'preferred_name_exists' => 'Preferred name already exists',
		'database_error' => 'Database error occurred',
		'cannot_delete_last_organizer' => 'Cannot delete the last organizer',
		'remove_failed' => 'Failed to remove users. Please try again.',
		'delete_failed' => 'Failed to delete user. Please try again.'
	];
	$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
	?>
	<p style="color: red; font-weight: bold;"><?= htmlspecialchars($errorMessage) ?></p>
<?php endif; ?>

<?php 
$roleLabels = [
	'organizer' => 'Organizers',
	'judge' => 'Judges', 
	'emcee' => 'Emcees',
	'contestant' => 'Contestants'
];
?>

<?php foreach ($roleLabels as $role => $label): ?>
    <?php if (!empty($usersByRole[$role])): ?>
        <div class="user-group">
            <button class="user-group-toggle" type="button" aria-expanded="false">
                <span class="twist">▶</span>
                <strong><?= htmlspecialchars($label) ?></strong>
                <span style="opacity:0.7; margin-left:6px;">(<?= count($usersByRole[$role]) ?>)</span>
            </button>
            <div class="user-group-content" style="display:none;">
        <table>
			<tr>
				<?php if ($role === 'contestant'): ?>
					<th>Number</th>
				<?php endif; ?>
				<th>Name</th>
				<th>Preferred Name</th>
				<th>Email</th>
				<th>Gender</th>
				<th>Pronouns</th>
				<?php if ($role === 'judge'): ?><th>Head Judge</th><?php endif; ?>
				<th>Can Login</th>
				<th>Actions</th>
			</tr>
			<?php foreach ($usersByRole[$role] as $user): ?>
				<tr>
                    <?php if ($role === 'contestant'): ?>
						<td><?= $user['contestant_number'] ? htmlspecialchars($user['contestant_number']) : '-' ?></td>
					<?php endif; ?>
					<td><?= htmlspecialchars($user['name']) ?></td>
					<td><?= htmlspecialchars($user['preferred_name'] ?? '-') ?></td>
					<td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
					<td><?= htmlspecialchars($user['gender'] ?? '-') ?></td>
					<td><?= htmlspecialchars($user['pronouns'] ?? '-') ?></td>
					<?php if ($role === 'judge'): ?><td><?= !empty($user['is_head_judge']) ? 'Yes' : 'No' ?></td><?php endif; ?>
					<td><?= !empty($user['password_hash']) ? 'Yes' : 'No' ?></td>
					<td>
						<?php if ($role === 'contestant'): ?>
							<a href="<?= url('people/contestants/' . urlencode($user['id']) . '/edit') ?>">Edit</a> |
							<a href="<?= url('people/contestants/' . urlencode($user['id']) . '/bio') ?>">Bio</a> |
							<a href="<?= url('admin/contestant/' . urlencode($user['id']) . '/scores') ?>">Scores</a> |
							<form method="post" action="<?= url('people/contestants/' . urlencode($user['id']) . '/delete') ?>" style="display:inline">
								<?= csrf_field() ?>
								<button type="submit" onclick="return confirm('Are you sure you want to delete this contestant?')">Delete</button>
							</form>
                        <?php else: ?>
							<a href="<?= url('admin/users/' . urlencode($user['id']) . '/edit') ?>">Edit</a> |
							<?php if ($role === 'judge'): ?>
								<a href="<?= url('people/judges/' . urlencode($user['judge_id'] ?? '') . '/bio') ?>">Bio</a> |
							<?php endif; ?>
                            <form method="post" action="<?= url('admin/users/' . urlencode($user['id']) . '/force-logout') ?>" style="display:inline">
                                <?= csrf_field() ?>
                                <button type="submit" onclick="return confirm('Force this user to log out?')">Force Logout</button>
                            </form> |
							<form method="post" action="<?= url('admin/users/' . urlencode($user['id']) . '/delete') ?>" style="display:inline">
								<?= csrf_field() ?>
								<button type="submit" onclick="return confirm('Are you sure you want to delete this user?')">Delete</button>
							</form>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
        </table>
            </div>
        </div>
	<?php endif; ?>
<?php endforeach; ?>

<?php if (empty($usersByRole) || array_sum(array_map('count', $usersByRole)) === 0): ?>
	<p>No users found. <a href="<?= url('users/new') ?>">Create the first user</a></p>
<?php endif; ?>

<style>
.user-group { border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-primary); margin: 16px 0; }
.user-group-toggle { width: 100%; text-align: left; padding: 12px 16px; border: 0; cursor: pointer; display: flex; align-items: center; gap: 8px; }
.user-group-toggle .twist { display: inline-block; transition: transform 0.2s ease; }
.user-group-toggle[aria-expanded="true"] .twist { transform: rotate(90deg); }
.user-group-content { padding: 10px 16px 16px 16px; }
.user-group-content table { width: 100%; border-collapse: collapse; }
.user-group-content th, .user-group-content td { padding: 10px; border-bottom: 1px solid var(--border-color); text-align: left; }
.user-group-content th { background: var(--bg-tertiary); }
.user-group-content a, .user-group-content button { margin-right: 6px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.user-group-toggle').forEach(function(btn){
        btn.addEventListener('click', function(){
            const expanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            const content = this.nextElementSibling;
            if (content) { content.style.display = expanded ? 'none' : 'block'; }
        });
    });
});
</script>

<?php if (isset($pagination) && $pagination['total_pages'] > 1): ?>
<div class="pagination-container" style="margin-top: 2rem; text-align: center;">
	<?= pagination_links($pagination, url('admin/users'), ['role' => $role ?? '']) ?>
	
	<div class="pagination-info" style="margin-top: 1rem; color: #666; font-size: 0.9rem;">
		Showing <?= $pagination['per_page'] * ($pagination['current_page'] - 1) + 1 ?> to 
		<?= min($pagination['per_page'] * $pagination['current_page'], $pagination['total_count']) ?> 
		of <?= $pagination['total_count'] ?> users
	</div>
</div>
<?php endif; ?>
