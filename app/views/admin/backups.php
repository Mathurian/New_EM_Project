<?php use function App\{url, hierarchical_back_url, home_url}; ?>
<h2>Database Backups</h2>

<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url('/admin') ?>" class="btn btn-secondary">← Back to Admin</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<?php if (!empty($_GET['success'])): ?>
	<?php 
	$successMessages = [
		'schema_backup_created' => 'Schema backup created successfully!',
		'full_backup_created' => 'Full database backup created successfully!',
		'backup_deleted' => 'Backup deleted successfully!',
		'settings_updated' => 'Backup settings updated successfully!',
		'scheduled_backups_run' => isset($_GET['message']) ? htmlspecialchars($_GET['message']) : 'Scheduled backups completed successfully!'
	];
	$successMessage = $successMessages[$_GET['success']] ?? 'Operation completed successfully!';
	?>
	<div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
	<?php 
	$errorMessages = [
		'schema_backup_failed' => 'Failed to create schema backup. Please try again.',
		'full_backup_failed' => 'Failed to create full backup. Please try again.',
		'backup_not_found' => 'Backup not found.',
		'backup_delete_failed' => 'Failed to delete backup. Please try again.',
		'settings_update_failed' => 'Failed to update backup settings. Please try again.',
		'scheduled_backups_failed' => isset($_GET['message']) ? 'Scheduled backup failed: ' . htmlspecialchars($_GET['message']) : 'Scheduled backup failed. Please try again.'
	];
	$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
	?>
	<div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>

<div class="row">
	<div class="col-6">
		<div class="card">
			<h4>On-Demand Backups</h4>
			<div class="backup-actions">
				<form method="post" action="<?= url('admin/backups/schema') ?>" style="display: inline-block;">
					<button type="submit" class="btn btn-primary">📋 Create Schema Backup</button>
				</form>
				<form method="post" action="<?= url('admin/backups/full') ?>" style="display: inline-block;">
					<button type="submit" class="btn btn-success">💾 Create Full Backup</button>
				</form>
				<a href="<?= url('admin/backups/run-scheduled') ?>" class="btn btn-warning">⏰ Run Scheduled Backups Now</a>
			</div>
			<div class="backup-info">
				<p><strong>Schema Backup:</strong> Exports only the database structure (tables, indexes, constraints)</p>
				<p><strong>Full Backup:</strong> Creates a complete copy of the database file</p>
			</div>
		</div>
	</div>
	
	<div class="col-6">
		<div class="card">
			<h4>Scheduled Backup Settings</h4>
			<?php if (empty($backupSettings)): ?>
				<div class="alert alert-warning">
					<p>No backup settings found. Default settings will be created automatically.</p>
					<p><a href="<?= url('admin/backups') ?>" class="btn btn-sm btn-primary">Refresh Page</a></p>
				</div>
			<?php else: ?>
				<form method="post" action="<?= url('admin/backups/settings') ?>">
					<?php foreach ($backupSettings as $setting): ?>
						<div class="form-group">
							<h5><?= ucfirst($setting['backup_type']) ?> Backup</h5>
							<div class="form-row">
								<div class="col-4">
									<label>
										<input type="checkbox" name="<?= $setting['backup_type'] ?>_enabled" value="1" <?= $setting['enabled'] ? 'checked' : '' ?>>
										Enable
									</label>
								</div>
							<div class="col-4">
								<label>Frequency:</label>
								<select name="<?= $setting['backup_type'] ?>_frequency" class="form-control" onchange="toggleFrequencyValue(this)">
									<option value="minutes" <?= $setting['frequency'] === 'minutes' ? 'selected' : '' ?>>Minutes</option>
									<option value="hours" <?= $setting['frequency'] === 'hours' ? 'selected' : '' ?>>Hours</option>
									<option value="daily" <?= $setting['frequency'] === 'daily' ? 'selected' : '' ?>>Daily</option>
									<option value="weekly" <?= $setting['frequency'] === 'weekly' ? 'selected' : '' ?>>Weekly</option>
									<option value="monthly" <?= $setting['frequency'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
								</select>
							</div>
							<div class="col-2">
								<label>Every:</label>
								<input type="number" name="<?= $setting['backup_type'] ?>_frequency_value" value="<?= $setting['frequency_value'] ?? 1 ?>" min="1" max="999" class="form-control" id="<?= $setting['backup_type'] ?>_frequency_value">
							</div>
							<div class="col-3">
								<label>Retention (days):</label>
								<input type="number" name="<?= $setting['backup_type'] ?>_retention" value="<?= $setting['retention_days'] ?>" min="1" max="365" class="form-control">
							</div>
							</div>
							<?php if ($setting['last_run']): ?>
								<p><small>Last run: <?= htmlspecialchars($setting['last_run']) ?></small></p>
							<?php endif; ?>
							<?php if ($setting['next_run']): ?>
								<p><small>Next run: <?= htmlspecialchars($setting['next_run']) ?></small></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
					<button type="submit" class="btn btn-primary">Update Settings</button>
				</form>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="card">
	<h4>Backup History</h4>
	<?php if (empty($backupLogs)): ?>
		<p>No backups found.</p>
	<?php else: ?>
		<div class="table-responsive">
			<table class="table">
				<thead>
					<tr>
						<th>Type</th>
						<th>File Name</th>
						<th>Size</th>
						<th>Status</th>
						<th>Created By</th>
						<th>Created At</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($backupLogs as $backup): ?>
						<tr>
							<td>
								<span class="badge badge-<?= $backup['backup_type'] === 'schema' ? 'primary' : ($backup['backup_type'] === 'full' ? 'success' : 'info') ?>">
									<?= ucfirst($backup['backup_type']) ?>
								</span>
							</td>
							<td><?= htmlspecialchars(basename($backup['file_path'])) ?></td>
							<td><?= $backup['file_size'] > 0 ? number_format($backup['file_size'] / 1024, 1) . ' KB' : 'N/A' ?></td>
							<td>
								<span class="badge badge-<?= $backup['status'] === 'success' ? 'success' : ($backup['status'] === 'failed' ? 'danger' : 'warning') ?>">
									<?= ucfirst($backup['status']) ?>
								</span>
								<?php if ($backup['status'] === 'failed' && $backup['error_message']): ?>
									<br><small class="text-danger"><?= htmlspecialchars($backup['error_message']) ?></small>
								<?php endif; ?>
							</td>
							<td><?= htmlspecialchars($backup['created_by_name'] ?? 'System') ?></td>
							<td><?= htmlspecialchars($backup['created_at']) ?></td>
							<td>
								<?php if ($backup['status'] === 'success'): ?>
									<a href="<?= url('admin/backups/' . urlencode($backup['id']) . '/download') ?>" class="btn btn-sm btn-primary">Download</a>
									<form method="post" action="<?= url('admin/backups/' . urlencode($backup['id']) . '/delete') ?>" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this backup?');">
										<button type="submit" class="btn btn-sm btn-danger">Delete</button>
									</form>
								<?php else: ?>
									<span class="text-muted">No actions available</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>
</div>

<div class="card">
	<h4>Backup Information</h4>
	<div class="backup-info">
		<p><strong>Backup Location:</strong> <?= htmlspecialchars($backupDirectory) ?></p>
		<p><strong>Schema Backups:</strong> Export the database structure (tables, indexes, constraints) as SQL files. Useful for recreating the database structure.</p>
		<p><strong>Full Backups:</strong> Create complete copies of the database file. Includes all data and can be used to restore the entire database.</p>
		<p><strong>Scheduled Backups:</strong> Automatically create backups based on your configured schedule. Use cron jobs or scheduled tasks to run <code>/admin/backups/run-scheduled</code> regularly.</p>
		<p><strong>Retention:</strong> Old backups are automatically deleted based on your retention settings to save disk space.</p>
		<p><a href="<?= url('admin/backups/debug-path') ?>" class="btn btn-sm btn-info">🔍 Debug Database Path</a></p>
		<p><a href="<?= url('admin/backups/debug-scheduled') ?>" class="btn btn-sm btn-info">⏰ Debug Scheduled Backups</a></p>
		<p><a href="<?= url('admin/backups/test-constraint') ?>" class="btn btn-sm btn-warning">🧪 Test Database Constraint</a></p>
		<p><a href="<?= url('admin/backups/force-constraint-update') ?>" class="btn btn-sm btn-danger" onclick="return confirm('This will force update the database constraint. Continue?')">🔧 Force Constraint Update</a></p>
		<p><a href="<?= url('admin/backups/force-constraint-update-simple') ?>" class="btn btn-sm btn-danger" onclick="return confirm('This will force update the database constraint (simple method). Continue?')">🔧 Force Constraint Update (Simple)</a></p>
		<p><a href="<?= url('admin/backups/run-cli-constraint-fix') ?>" class="btn btn-sm btn-danger" onclick="return confirm('This will run the command-line constraint fix (most reliable). Continue?')">🔧 Run CLI Constraint Fix</a></p>
		<p><a href="<?= url('admin/backups/run-sqlite3-constraint-fix') ?>" class="btn btn-sm btn-danger" onclick="return confirm('This will run the sqlite3 command-line constraint fix (bypasses PHP issues). Continue?')">🔧 Run SQLite3 Constraint Fix</a></p>
		<p><a href="<?= url('admin/backups/run-shell-constraint-fix') ?>" class="btn btn-sm btn-danger" onclick="return confirm('This will run the shell script constraint fix (stops web server). Continue?')">🔧 Run Shell Constraint Fix</a></p>
		<p><a href="<?= url('admin/backups/fix-timestamps') ?>" class="btn btn-sm btn-warning" onclick="return confirm('This will fix backup timestamp inconsistencies. Continue?')">🕒 Fix Backup Timestamps</a></p>
		<p><strong>Debug Form Submission:</strong></p>
		<form method="post" action="<?= url('admin/backups/debug-form') ?>" style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">
			<div class="form-group">
				<label>Test Frequency:</label>
				<select name="test_frequency" class="form-control">
					<option value="minutes">Minutes</option>
					<option value="hours">Hours</option>
					<option value="daily">Daily</option>
				</select>
			</div>
			<div class="form-group">
				<label>Test Value:</label>
				<input type="number" name="test_value" value="5" class="form-control">
			</div>
			<button type="submit" class="btn btn-sm btn-primary">Test Form Submission</button>
		</form>
	</div>
	<h6>Setting up Scheduled Backups:</h6>
	<p>Add this to your crontab to run scheduled backups every hour:</p>
	<code>0 * * * * curl -s "http://your-domain.com/admin/backups/run-scheduled" > /dev/null 2>&1</code>
</div>

<style>
.backup-actions {
	margin-bottom: 20px;
}
.backup-actions form {
	margin-right: 10px;
}
.backup-info {
	background: #f8f9fa;
	padding: 15px;
	border-radius: 5px;
	margin-top: 15px;
}
.backup-info p {
	margin: 5px 0;
}
.form-row {
	display: flex;
	gap: 15px;
	margin-bottom: 10px;
}
.form-row .col-4 {
	flex: 1;
}
.form-row label {
	display: block;
	margin-bottom: 5px;
	font-weight: bold;
}
.table-responsive {
	overflow-x: auto;
}
</style>

<script>
function toggleFrequencyValue(selectElement) {
	const frequencyValue = selectElement.parentElement.nextElementSibling.querySelector('input[type="number"]');
	const frequency = selectElement.value;
	
	// Set appropriate max values based on frequency
	switch (frequency) {
		case 'minutes':
			frequencyValue.max = 59;
			frequencyValue.placeholder = '1-59';
			break;
		case 'hours':
			frequencyValue.max = 23;
			frequencyValue.placeholder = '1-23';
			break;
		case 'daily':
			frequencyValue.max = 30;
			frequencyValue.placeholder = '1-30';
			break;
		case 'weekly':
			frequencyValue.max = 4;
			frequencyValue.placeholder = '1-4';
			break;
		case 'monthly':
			frequencyValue.max = 12;
			frequencyValue.placeholder = '1-12';
			break;
	}
	
	// Ensure the current value is within the new max
	if (parseInt(frequencyValue.value) > parseInt(frequencyValue.max)) {
		frequencyValue.value = frequencyValue.max;
	}
}

// Initialize frequency value fields on page load
document.addEventListener('DOMContentLoaded', function() {
	const frequencySelects = document.querySelectorAll('select[name$="_frequency"]');
	frequencySelects.forEach(toggleFrequencyValue);
});
</script>
