<?php use function App\{url, is_organizer, hierarchical_back_url, home_url}; ?>
<h2>System Settings</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<?php if (!empty($_GET['success'])): ?>
	<?php 
	$successMessages = [
		'settings_updated' => 'Settings updated successfully!',
		'email_test_success' => 'Test email sent successfully! Check your inbox.'
	];
	$successMessage = $successMessages[$_GET['success']] ?? 'Operation completed successfully!';
	?>
	<div class="alert alert-success">
		<?= htmlspecialchars($successMessage) ?>
		<?php if ($_GET['success'] === 'email_test_success' && !empty($_GET['test_email'])): ?>
			<br><small>Test email sent to: <?= htmlspecialchars($_GET['test_email']) ?></small>
		<?php endif; ?>
	</div>
<?php endif; ?>

<?php if (!empty($_GET['error'])): ?>
	<?php 
	$errorMessages = [
		'invalid_timeout' => 'Invalid session timeout value. Please enter a positive number.',
		'email_test_failed' => 'Test email failed to send. Check your SMTP settings and try again.',
		'email_test_exception' => 'Email test failed with error: ' . htmlspecialchars($_GET['details'] ?? 'Unknown error')
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
			<h4>Email Test Tool</h4>
			<p>Test your SMTP configuration by sending a test email:</p>
			<form method="post" action="<?= url('admin/settings/test-email') ?>" style="display: inline-block;">
				<input type="email" name="test_email" placeholder="test@example.com" value="<?= htmlspecialchars($settings['smtp_from_email'] ?? '') ?>" style="padding: 6px; margin-right: 8px; width: 200px;" />
				<button type="submit" class="btn btn-sm btn-secondary">📧 Test Email</button>
			</form>
			<p style="margin-top: 8px; font-size: 0.9em; color: #666;">
				Leave email blank to use the configured "From Email" address.
			</p>
		</div>
		
		<div style="margin-bottom: 20px; padding: 15px; background: #e8f5e8; border: 1px solid #c3e6c3; border-radius: 5px;">
			<h4>Logging Test Tools</h4>
			<p>Use these tools to test and verify logging functionality:</p>
			<p><a href="<?= url('admin/settings/test-log-level') ?>" class="btn btn-sm btn-secondary" target="_blank">🧪 Test Log Level</a> - Test all log levels and filtering</p>
			<p><a href="<?= url('admin/settings/test-logging') ?>" class="btn btn-sm btn-secondary" target="_blank">📝 Test Logging</a> - Send test log messages</p>
		</div>

		<div style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">
			<h3>Email (PHPMailer / SMTP)</h3>
			<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
				<label>
					<strong>Enabled</strong><br>
					<select name="smtp_enabled" style="width: 100%; padding: 6px; margin-top: 5px;">
						<option value="1" <?= (($settings['smtp_enabled'] ?? '1') === '1') ? 'selected' : '' ?>>Yes</option>
						<option value="0" <?= (($settings['smtp_enabled'] ?? '1') === '0') ? 'selected' : '' ?>>No</option>
					</select>
				</label>
				<label>
					<strong>From Email</strong><br>
					<input type="email" name="smtp_from_email" value="<?= htmlspecialchars($settings['smtp_from_email'] ?? '') ?>" style="width: 100%; padding: 6px; margin-top: 5px;" />
				</label>
				<label>
					<strong>From Name</strong><br>
					<input type="text" name="smtp_from_name" value="<?= htmlspecialchars($settings['smtp_from_name'] ?? '') ?>" style="width: 100%; padding: 6px; margin-top: 5px;" />
				</label>
				<label>
					<strong>Host</strong><br>
					<input type="text" name="smtp_host" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>" style="width: 100%; padding: 6px; margin-top: 5px;" />
				</label>
				<label>
					<strong>Port</strong><br>
					<input type="number" name="smtp_port" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>" style="width: 100%; padding: 6px; margin-top: 5px;" placeholder="587 for STARTTLS, 465 for SSL" />
				</label>
				<label>
					<strong>Security</strong><br>
					<select name="smtp_secure" style="width: 100%; padding: 6px; margin-top: 5px;">
						<option value="" <?= (($settings['smtp_secure'] ?? 'tls') === '') ? 'selected' : '' ?>>None</option>
						<option value="tls" <?= (($settings['smtp_secure'] ?? 'tls') === 'tls') ? 'selected' : '' ?>>TLS (STARTTLS)</option>
						<option value="ssl" <?= (($settings['smtp_secure'] ?? 'tls') === 'ssl') ? 'selected' : '' ?>>SSL</option>
					</select>
				</label>
				<label>
					<strong>Auth</strong><br>
					<select name="smtp_auth" style="width: 100%; padding: 6px; margin-top: 5px;">
						<option value="0" <?= (($settings['smtp_auth'] ?? '1') === '0') ? 'selected' : '' ?>>Disabled</option>
						<option value="1" <?= (($settings['smtp_auth'] ?? '1') === '1') ? 'selected' : '' ?>>Enabled</option>
					</select>
				</label>
				<label>
					<strong>Username</strong><br>
					<input type="text" name="smtp_username" value="<?= htmlspecialchars($settings['smtp_username'] ?? '') ?>" style="width: 100%; padding: 6px; margin-top: 5px;" />
				</label>
				<label>
					<strong>Password</strong><br>
					<input type="password" name="smtp_password" value="<?= htmlspecialchars($settings['smtp_password'] ?? '') ?>" style="width: 100%; padding: 6px; margin-top: 5px;" />
				</label>
			</div>
			<p style="margin-top:10px; color:#666; font-size:0.9em;">
				<strong>Common SMTP Settings:</strong><br>
				• Gmail: smtp.gmail.com, Port 587, TLS, Auth enabled<br>
				• Outlook: smtp-mail.outlook.com, Port 587, TLS, Auth enabled<br>
				• Yahoo: smtp.mail.yahoo.com, Port 587, TLS, Auth enabled<br>
				• Custom: Check with your email provider for settings<br><br>
				If a field is left blank, environment variables will be used as fallback.
			</p>
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
