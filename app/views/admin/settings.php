<?php use function App\{url, is_organizer, hierarchical_back_url, home_url}; ?>
<h2>System Settings</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<?php if (!empty($_GET['success'])): ?>
	<?php 
	$successMessages = [
		'settings_updated' => 'Settings updated successfully!'
	];
	$successMessage = $successMessages[$_GET['success']] ?? 'Operation completed successfully!';
	?>
	<div class="alert alert-success"><?= htmlspecialchars($successMessage) ?></div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
	<?php 
	$errorMessages = [
		'invalid_timeout' => 'Invalid session timeout value. Please enter a positive number.'
	];
	$errorMessage = $errorMessages[$_GET['error']] ?? 'An error occurred';
	?>
	<div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>

<div class="settings-container">
	<form method="post" action="<?= url('admin/settings') ?>">
		<div class="card">
			<h3>Session Management</h3>
			
			<label style="display: block; margin-bottom: 10px;">
				<strong>Session Timeout (seconds):</strong><br>
				<input type="number" name="session_timeout" 
					   value="<?= htmlspecialchars($settings['session_timeout'] ?? '1800') ?>" 
					   min="60" max="86400" step="60" required 
					   style="width: 200px; padding: 5px; margin-top: 5px;" />
				<br>
				<small style="color: #666;">
					Current: <?= htmlspecialchars($settings['session_timeout'] ?? '1800') ?> seconds 
					(<?= round(($settings['session_timeout'] ?? 1800) / 60, 1) ?> minutes)
				</small>
			</label>
			
			<div style="margin-top: 10px; font-size: 0.9em; color: #666;">
				<p><strong>Description:</strong> <?= htmlspecialchars($settingsArray['session_timeout']['description'] ?? 'Session timeout in seconds') ?></p>
				<?php if (!empty($settingsArray['session_timeout']['updated_at'])): ?>
					<p><strong>Last Updated:</strong> <?= htmlspecialchars($settingsArray['session_timeout']['updated_at']) ?></p>
				<?php endif; ?>
			</div>
		</div>
		
		<div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">
			<h3>Logging Configuration</h3>
			
			<label style="display: block; margin-bottom: 10px;">
				<strong>Log Level:</strong><br>
				<select name="log_level" required style="width: 200px; padding: 5px; margin-top: 5px;">
					<option value="debug" <?= ($settings['log_level'] ?? 'info') === 'debug' ? 'selected' : '' ?>>Debug - All messages</option>
					<option value="info" <?= ($settings['log_level'] ?? 'info') === 'info' ? 'selected' : '' ?>>Info - Informational messages and above</option>
					<option value="warn" <?= ($settings['log_level'] ?? 'info') === 'warn' ? 'selected' : '' ?>>Warning - Warning messages and above</option>
					<option value="error" <?= ($settings['log_level'] ?? 'info') === 'error' ? 'selected' : '' ?>>Error - Error messages only</option>
				</select>
				<br>
				<small style="color: #666;">
					Database: <?= htmlspecialchars(ucfirst($settings['log_level'] ?? 'info')) ?> level
					| Active Logger: <strong><?= htmlspecialchars(ucfirst($currentLoggerLevel)) ?></strong> level
				</small>
			</label>
			
			<div style="margin-top: 10px; font-size: 0.9em; color: #666;">
				<p><strong>Description:</strong> <?= htmlspecialchars($settingsArray['log_level']['description'] ?? 'Logging level for system activity') ?></p>
				<?php if (!empty($settingsArray['log_level']['updated_at'])): ?>
					<p><strong>Last Updated:</strong> <?= htmlspecialchars($settingsArray['log_level']['updated_at']) ?></p>
				<?php endif; ?>
			</div>
		</div>
		
		<div style="margin-bottom: 20px; padding: 15px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
			<h4>Log Level Guidelines</h4>
			<ul style="margin: 10px 0; padding-left: 20px;">
				<li><strong>Debug:</strong> Records all actions including data access and detailed operations</li>
				<li><strong>Info:</strong> Records normal operations like logins, scoring, user management</li>
				<li><strong>Warning:</strong> Records potential issues like failed logins, user deletions</li>
				<li><strong>Error:</strong> Records only system errors and critical issues</li>
			</ul>
		</div>
		
		<div style="margin-bottom: 20px; padding: 15px; background: #e8f5e8; border: 1px solid #c3e6c3; border-radius: 5px;">
			<h4>Logging Test Tools</h4>
			<p>Use these tools to test and verify logging functionality:</p>
			<p><a href="<?= url('admin/settings/test-log-level') ?>" class="btn btn-sm btn-secondary">🧪 Test Log Level</a> - Test all log levels and filtering</p>
			<p><a href="<?= url('admin/settings/test-logging') ?>" class="btn btn-sm btn-secondary">📝 Test Logging</a> - Send test log messages</p>
		</div>
		
		<button type="submit" style="background: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
			Update Settings
		</button>
	</form>
</div>

<div style="margin-top: 30px; padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px;">
	<h4>Additional Admin Tools</h4>
	<p>
		<a href="<?= url('admin/logs') ?>" style="color: #0c5460; text-decoration: none; font-weight: bold;">
			📋 View Activity Logs
		</a> - Monitor all system activity and user actions
	</p>
</div>
