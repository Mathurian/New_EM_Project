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
		<!-- Scheduled Backup Settings moved below history -->
	</div>
</div>

<div class="card">
	<h4>Backup History</h4>
	<?php if (empty($backupLogs)): ?>
		<p>No backups found.</p>
	<?php else: ?>
    <div class="table-responsive">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                <strong>Recent Backups</strong>
                <button type="button" class="btn btn-secondary btn-sm" id="toggle-backups" aria-expanded="false" aria-controls="backups-table">Show All</button>
            </div>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.9em;" id="backups-table" data-collapsed="true">
				<thead>
					<tr style="background: #f8f9fa;">
						<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Type</th>
						<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">File Name</th>
						<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Size</th>
						<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Status</th>
						<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Created By</th>
						<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Created At</th>
						<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Actions</th>
					</tr>
				</thead>
				<tbody>
                    <?php $rowIndex = 0; foreach ($backupLogs as $backup): $rowIndex++; ?>
                        <tr class="backup-row" data-index="<?= $rowIndex ?>">
							<td style="border: 1px solid #dee2e6; padding: 8px;">
								<span class="badge badge-<?= $backup['backup_type'] === 'schema' ? 'primary' : ($backup['backup_type'] === 'full' ? 'success' : 'info') ?>">
									<?= ucfirst($backup['backup_type']) ?>
								</span>
							</td>
							<td style="border: 1px solid #dee2e6; padding: 8px;"><?= htmlspecialchars(basename($backup['file_path'])) ?></td>
							<td style="border: 1px solid #dee2e6; padding: 8px;"><?= $backup['file_size'] > 0 ? number_format($backup['file_size'] / 1024, 1) . ' KB' : 'N/A' ?></td>
							<td style="border: 1px solid #dee2e6; padding: 8px;">
								<span class="badge badge-<?= $backup['status'] === 'success' ? 'success' : ($backup['status'] === 'failed' ? 'danger' : 'warning') ?>">
									<?= ucfirst($backup['status']) ?>
								</span>
								<?php if ($backup['status'] === 'failed' && $backup['error_message']): ?>
									<br><small class="text-danger"><?= htmlspecialchars($backup['error_message']) ?></small>
								<?php endif; ?>
							</td>
							<td style="border: 1px solid #dee2e6; padding: 8px;"><?= htmlspecialchars($backup['created_by_name'] ?? 'System') ?></td>
							<td style="border: 1px solid #dee2e6; padding: 8px;"><span class="log-time" data-iso="<?= htmlspecialchars($backup['created_at']) ?>"><?= htmlspecialchars($backup['created_at']) ?></span></td>
							<td style="border: 1px solid #dee2e6; padding: 8px;">
								<?php if ($backup['status'] === 'success'): ?>
									<a href="<?= url('admin/backups/' . urlencode($backup['id']) . '/download') ?>" class="btn btn-xs btn-primary" style="padding: 2px 6px; font-size: 0.75em; margin-right: 4px;">Download</a>
									<form method="post" action="<?= url('admin/backups/' . urlencode($backup['id']) . '/delete') ?>" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this backup?');">
										<button type="submit" class="btn btn-xs btn-danger" style="padding: 2px 6px; font-size: 0.75em;">Delete</button>
									</form>
								<?php else: ?>
									<span class="text-muted" style="font-size: 0.8em;">No actions available</span>
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
						<p><small>Last run: <span class="log-time" data-iso="<?= htmlspecialchars($setting['last_run']) ?>"><?= htmlspecialchars($setting['last_run']) ?></span></small></p>
					<?php endif; ?>
					<?php if ($setting['next_run']): ?>
						<p><small>Next run: <span class="log-time" data-iso="<?= htmlspecialchars($setting['next_run']) ?>"><?= htmlspecialchars($setting['next_run']) ?></span></small></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
			<button type="submit" class="btn btn-primary">Update Settings</button>
		</form>
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
		<p><a href="<?= url('admin/backups/restore-settings') ?>" class="btn btn-sm btn-info" onclick="return confirm('This will restore default backup settings if they are missing. Continue?')">🔄 Restore Backup Settings</a></p>
		<p><a href="<?= url('admin/backups/reset-sessions') ?>" class="btn btn-sm btn-warning" onclick="return confirm('This will reset all user session versions to fix login issues. Continue?')">🔐 Reset Session Versions</a></p>
		<p><a href="<?= url('admin/backups/debug-scheduled') ?>" class="btn btn-sm btn-secondary" target="_blank">🔍 Debug Scheduled Backups</a></p>
		<p><a href="<?= url('admin/backups/check-time') ?>" class="btn btn-sm btn-secondary" target="_blank">🕒 Check System Time</a></p>
		<p><a href="<?= url('admin/backups/debug-settings') ?>" class="btn btn-sm btn-secondary" target="_blank">🔧 Debug Backup Settings</a></p>
	</div>
	<h6>Setting up Scheduled Backups:</h6>
	<?php if (!empty($backupSettings)): ?>
		<?php 
		$enabledBackups = array_filter($backupSettings, function($s) { return $s['enabled']; });
		$mostFrequentInterval = null;
		$mostFrequentMinutes = 60; // Default to hourly
		
		foreach ($enabledBackups as $backup) {
			$minutes = 60; // Default
			switch ($backup['frequency']) {
				case 'minutes':
					$minutes = $backup['frequency_value'];
					break;
				case 'hours':
					$minutes = $backup['frequency_value'] * 60;
					break;
				case 'daily':
					$minutes = $backup['frequency_value'] * 24 * 60;
					break;
				case 'weekly':
					$minutes = $backup['frequency_value'] * 7 * 24 * 60;
					break;
				case 'monthly':
					$minutes = $backup['frequency_value'] * 30 * 24 * 60; // Approximate
					break;
			}
			
			if ($minutes < $mostFrequentMinutes) {
				$mostFrequentMinutes = $minutes;
				$mostFrequentInterval = $backup['frequency'] . ' (' . $backup['frequency_value'] . ')';
			}
		}
		
		// Generate cron expression
		$cronExpression = '0 * * * *'; // Default hourly
		if ($mostFrequentMinutes <= 1) {
			$cronExpression = '* * * * *'; // Every minute
		} elseif ($mostFrequentMinutes <= 5) {
			$cronExpression = '*/5 * * * *'; // Every 5 minutes
		} elseif ($mostFrequentMinutes <= 15) {
			$cronExpression = '*/15 * * * *'; // Every 15 minutes
		} elseif ($mostFrequentMinutes <= 30) {
			$cronExpression = '*/30 * * * *'; // Every 30 minutes
		} elseif ($mostFrequentMinutes < 60) {
			$cronExpression = '*/' . $mostFrequentMinutes . ' * * * *'; // Custom minutes
		}
		?>
		
		<?php if (!empty($enabledBackups)): ?>
			<div class="alert alert-info">
				<p><strong>Current Schedule:</strong></p>
				<?php foreach ($enabledBackups as $backup): ?>
					<p>• <?= ucfirst($backup['backup_type']) ?> backup: Every <?= $backup['frequency_value'] ?> <?= $backup['frequency'] ?></p>
				<?php endforeach; ?>
				<p><strong>Recommended cron frequency:</strong> <?= $mostFrequentInterval ?: 'hourly' ?></p>
			</div>
			
			<p>Add this to your crontab to run scheduled backups:</p>
			<div class="code-block">
				<code><?= $cronExpression ?> curl -s "<?= $_SERVER['HTTP_HOST'] ?>/admin/backups/run-scheduled" > /dev/null 2>&1</code>
			</div>
			
			<p><strong>To set up:</strong></p>
			<ol>
				<li>Open terminal and run: <code>crontab -e</code></li>
				<li>Add the line above</li>
				<li>Save and exit</li>
			</ol>
		<?php else: ?>
			<div class="alert alert-warning">
				<p><strong>No scheduled backups enabled.</strong></p>
				<p>Enable scheduled backups below, then return here for cron instructions.</p>
			</div>
		<?php endif; ?>
	<?php else: ?>
		<div class="alert alert-warning">
			<p>Configure scheduled backup settings below to see cron instructions.</p>
		</div>
	<?php endif; ?>
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
.code-block {
	background-color: #f8f9fa;
	border: 1px solid #e9ecef;
	border-radius: 4px;
	padding: 10px;
	margin: 10px 0;
	font-family: monospace;
}
.code-block code {
	background: none;
	padding: 0;
	color: #e83e8c;
	font-size: 0.9em;
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
	
	// Convert ISO timestamps to the user's local timezone (same as activity logs)
	const nodes = document.querySelectorAll('.log-time');
	nodes.forEach(function(node) {
		const iso = node.getAttribute('data-iso');
		if (!iso) return;
		const date = new Date(iso);
		if (isNaN(date.getTime())) return;
		const opts = { year: 'numeric', month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' };
		node.textContent = date.toLocaleString(undefined, opts);
		node.title = 'Local time';
	});

    // Limit table length and allow expansion
    (function(){
        const table = document.getElementById('backups-table');
        if (!table) return;
        const rows = Array.from(table.querySelectorAll('tbody tr.backup-row'));
        const toggleBtn = document.getElementById('toggle-backups');
        const COLLAPSED_COUNT = 10; // show first 10 by default

        function applyState() {
            const collapsed = table.getAttribute('data-collapsed') === 'true';
            rows.forEach((row, idx) => {
                row.style.display = collapsed && idx >= COLLAPSED_COUNT ? 'none' : '';
            });
            if (toggleBtn) {
                toggleBtn.textContent = collapsed ? 'Show All' : 'Show Less';
                toggleBtn.setAttribute('aria-expanded', String(!collapsed));
            }
        }

        applyState();

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(){
                const collapsed = table.getAttribute('data-collapsed') === 'true';
                table.setAttribute('data-collapsed', String(!collapsed));
                applyState();
            });
        }
    })();
});
</script>
