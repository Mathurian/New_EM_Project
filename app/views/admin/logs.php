<?php use function App\{url, is_organizer, hierarchical_back_url, home_url}; ?>
<h2>Activity Logs</h2>
<div class="navigation-buttons">
	<a href="<?= hierarchical_back_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="card">
	<p><strong>Total Logs:</strong> <?= number_format($totalLogs) ?> entries</p>
	<p><strong>Current Page:</strong> <?= $page ?> of <?= $totalPages ?></p>
	<p><strong>Current Log Level:</strong> <?= htmlspecialchars(ucfirst($currentLogLevel)) ?></p>
</div>

<!-- Advanced Filtering (collapsible) -->
<div class="card" id="filters-card">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
        <h4 style="margin:0;">Advanced Filters</h4>
        <button type="button" class="btn btn-secondary btn-sm" id="toggle-filters" aria-expanded="false" aria-controls="filters-body">Show Filters</button>
    </div>
    <form method="get" action="<?= url('admin/logs') ?>" class="filter-form" id="filters-body" style="display:none; margin-top:12px;">
		<div>
			<label for="level">Log Level:</label>
			<select name="level" id="level" style="width: 100%; padding: 5px;">
				<option value="all" <?= $logLevel === 'all' ? 'selected' : '' ?>>All Levels</option>
				<option value="debug" <?= $logLevel === 'debug' ? 'selected' : '' ?>>Debug</option>
				<option value="info" <?= $logLevel === 'info' ? 'selected' : '' ?>>Info</option>
				<option value="warn" <?= $logLevel === 'warn' ? 'selected' : '' ?>>Warning</option>
				<option value="error" <?= $logLevel === 'error' ? 'selected' : '' ?>>Error</option>
			</select>
		</div>
		
		<div>
			<label for="role">User Role:</label>
			<select name="role" id="role" style="width: 100%; padding: 5px;">
				<option value="all" <?= $userRole === 'all' ? 'selected' : '' ?>>All Roles</option>
				<?php foreach ($availableRoles as $role): ?>
					<option value="<?= htmlspecialchars($role) ?>" <?= $userRole === $role ? 'selected' : '' ?>>
						<?= htmlspecialchars(ucfirst($role)) ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		
		<div>
			<label for="action">Action:</label>
			<select name="action" id="action" style="width: 100%; padding: 5px;">
				<option value="" <?= empty($action) ? 'selected' : '' ?>>All Actions</option>
				<?php foreach ($availableActions as $actionOption): ?>
					<option value="<?= htmlspecialchars($actionOption) ?>" <?= $action === $actionOption ? 'selected' : '' ?>>
						<?= htmlspecialchars(str_replace('_', ' ', ucfirst($actionOption))) ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>
		
		<div>
			<label for="date_from">From Date:</label>
			<input type="date" name="date_from" id="date_from" value="<?= htmlspecialchars($dateFrom) ?>" style="width: 100%; padding: 5px;">
		</div>
		
		<div>
			<label for="date_to">To Date:</label>
			<input type="date" name="date_to" id="date_to" value="<?= htmlspecialchars($dateTo) ?>" style="width: 100%; padding: 5px;">
		</div>
		
		<div>
			<button type="submit" class="btn btn-primary">Apply Filters</button>
			<a href="<?= url('admin/logs') ?>" class="btn btn-secondary" style="margin-left: 10px;">Clear Filters</a>
		</div>
    </form>
</div>

<?php if (empty($logs)): ?>
	<p>No activity logs found.</p>
<?php else: ?>
	<table style="width: 100%; border-collapse: collapse; font-size: 0.9em;">
		<thead>
			<tr style="background: #f8f9fa;">
				<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Timestamp</th>
				<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Level</th>
				<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">User</th>
				<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Role</th>
				<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Action</th>
				<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Resource</th>
				<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">Details</th>
				<th style="border: 1px solid #dee2e6; padding: 8px; text-align: left;">IP Address</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($logs as $log): ?>
				<tr>
				<td style="border: 1px solid #dee2e6; padding: 8px;" class="log-time" data-iso="<?= htmlspecialchars($log['created_at']) ?>">
					<?= htmlspecialchars($log['created_at']) ?>
				</td>
					<td style="border: 1px solid #dee2e6; padding: 8px;">
						<?php
						$levelColors = [
							'debug' => '#6c757d',
							'info' => '#17a2b8',
							'warn' => '#ffc107',
							'error' => '#dc3545'
						];
						$levelColor = $levelColors[$log['log_level'] ?? 'info'] ?? '#6c757d';
						?>
						<span style="background: <?= $levelColor ?>; color: white; padding: 2px 6px; border-radius: 3px; font-size: 0.8em; font-weight: bold;">
							<?= htmlspecialchars(strtoupper($log['log_level'] ?? 'info')) ?>
						</span>
					</td>
					<td style="border: 1px solid #dee2e6; padding: 8px;">
						<?= htmlspecialchars($log['user_name'] ?? 'System') ?>
					</td>
					<td style="border: 1px solid #dee2e6; padding: 8px;">
						<span style="background: <?= $log['user_role'] === 'organizer' ? '#d4edda' : ($log['user_role'] === 'judge' ? '#fff3cd' : '#f8d7da') ?>; padding: 2px 6px; border-radius: 3px; font-size: 0.8em;">
							<?= htmlspecialchars(ucfirst($log['user_role'] ?? 'Guest')) ?>
						</span>
					</td>
					<td style="border: 1px solid #dee2e6; padding: 8px;">
						<?php
						$actionColors = [
							'login_success' => '#28a745',
							'login_failed' => '#dc3545',
							'logout' => '#6c757d',
							'score_submission' => '#007bff',
							'score_certification' => '#17a2b8',
							'user_creation' => '#28a745',
							'user_deletion' => '#dc3545',
							'contest_archive' => '#6c757d',
							'admin_' => '#ffc107'
						];
						$actionColor = '#6c757d';
						foreach ($actionColors as $prefix => $color) {
							if (strpos($log['action'], $prefix) === 0) {
								$actionColor = $color;
								break;
							}
						}
						?>
						<span style="color: <?= $actionColor ?>; font-weight: bold;">
							<?= htmlspecialchars(str_replace('_', ' ', ucfirst($log['action']))) ?>
						</span>
					</td>
					<td style="border: 1px solid #dee2e6; padding: 8px;">
						<?php if ($log['resource_type']): ?>
							<?= htmlspecialchars(ucfirst($log['resource_type'])) ?>
							<?php if ($log['resource_id']): ?>
								<br><small style="color: #666;">ID: <?= htmlspecialchars(substr($log['resource_id'], 0, 8)) ?>...</small>
							<?php endif; ?>
						<?php else: ?>
							-
						<?php endif; ?>
					</td>
					<td style="border: 1px solid #dee2e6; padding: 8px; max-width: 200px; word-wrap: break-word;">
						<?= htmlspecialchars($log['details'] ?? '-') ?>
					</td>
					<td style="border: 1px solid #dee2e6; padding: 8px; font-family: monospace; font-size: 0.8em;">
						<?= htmlspecialchars($log['ip_address'] ?? 'Unknown') ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	
	<!-- Pagination -->
	<?php if ($totalPages > 1): ?>
		<div style="margin-top: 20px; text-align: center;">
			<?php 
			$filterParams = [];
			if ($logLevel !== 'all') $filterParams[] = 'level=' . urlencode($logLevel);
			if ($userRole !== 'all') $filterParams[] = 'role=' . urlencode($userRole);
			if (!empty($action)) $filterParams[] = 'action=' . urlencode($action);
			if (!empty($dateFrom)) $filterParams[] = 'date_from=' . urlencode($dateFrom);
			if (!empty($dateTo)) $filterParams[] = 'date_to=' . urlencode($dateTo);
			$filterQuery = !empty($filterParams) ? '&' . implode('&', $filterParams) : '';
			?>
			
			<?php if ($page > 1): ?>
				<a href="<?= url('admin/logs?page=' . ($page - 1) . $filterQuery) ?>" style="margin-right: 10px; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 3px;">← Previous</a>
			<?php endif; ?>
			
			<span style="margin: 0 10px;">
				Page <?= $page ?> of <?= $totalPages ?>
			</span>
			
			<?php if ($page < $totalPages): ?>
				<a href="<?= url('admin/logs?page=' . ($page + 1) . $filterQuery) ?>" style="margin-left: 10px; padding: 5px 10px; background: #007bff; color: white; text-decoration: none; border-radius: 3px;">Next →</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>
<?php endif; ?>

<script>
// Convert ISO timestamps to the user's local timezone
document.addEventListener('DOMContentLoaded', function() {
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
    // Toggle filter visibility
    const toggleBtn = document.getElementById('toggle-filters');
    const filtersBody = document.getElementById('filters-body');
    if (toggleBtn && filtersBody) {
        toggleBtn.addEventListener('click', function() {
            const isOpen = filtersBody.style.display !== 'none';
            filtersBody.style.display = isOpen ? 'none' : 'block';
            this.textContent = isOpen ? 'Show Filters' : 'Hide Filters';
            this.setAttribute('aria-expanded', String(!isOpen));
        });
        // If any filter is active, open by default
        const hasActive = (
            '<?= $logLevel ?>' && '<?= $logLevel ?>' !== 'all'
        ) || (
            '<?= $userRole ?>' && '<?= $userRole ?>' !== 'all'
        ) || '<?= $action ?>' || '<?= $dateFrom ?>' || '<?= $dateTo ?>';
        if (hasActive) {
            filtersBody.style.display = 'block';
            toggleBtn.textContent = 'Hide Filters';
            toggleBtn.setAttribute('aria-expanded', 'true');
        }
    }
});
</script>

<div style="margin-top: 30px; padding: 15px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px;">
	<h4>Log Information</h4>
	<p><strong>Log Retention:</strong> Activity logs are kept indefinitely for audit purposes.</p>
	<p><strong>Privacy:</strong> IP addresses and user agents are logged for security monitoring.</p>
	<p><strong>Actions Tracked:</strong> All user logins, scoring activities, user management, and admin actions.</p>
</div>
