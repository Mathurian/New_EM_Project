<?php use function App\{url, is_organizer}; use App\DB; ?>
<h2>Admin Dashboard</h2>
<p><a href="/">← Back to Home</a></p>

<div class="dashboard-grid">
	<!-- Quick Stats -->
	<div class="stats-section">
		<h3>📊 System Overview</h3>
		<div class="stats-grid">
			<?php
			// Get the current active contest (most recent one)
			$currentContest = DB::pdo()->query('SELECT * FROM contests ORDER BY start_date DESC LIMIT 1')->fetch(\PDO::FETCH_ASSOC);
			$currentContestId = $currentContest['id'] ?? null;
			
			$stats = [
				'Total Users' => DB::pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn(),
				'Active Judges' => DB::pdo()->query('SELECT COUNT(*) FROM users WHERE role = "judge"')->fetchColumn(),
				'Contestants' => DB::pdo()->query('SELECT COUNT(*) FROM users WHERE role = "contestant"')->fetchColumn(),
				'Emcees' => DB::pdo()->query('SELECT COUNT(*) FROM users WHERE role = "emcee"')->fetchColumn(),
				'Contests' => DB::pdo()->query('SELECT COUNT(*) FROM contests')->fetchColumn(),
				'Categories' => DB::pdo()->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
				'Subcategories' => DB::pdo()->query('SELECT COUNT(*) FROM subcategories')->fetchColumn(),
				'Templates' => DB::pdo()->query('SELECT COUNT(*) FROM subcategory_templates')->fetchColumn()
			];
			?>
			<?php foreach ($stats as $label => $count): ?>
				<?php
				// Define clickable URLs for each stat
				$clickableUrls = [
					'Total Users' => url('admin/users'),
					'Active Judges' => url('admin/judges'),
					'Contestants' => url('admin/contestants'),
					'Emcees' => url('admin/users'), // Use users page since no dedicated emcee admin page
					'Contests' => $currentContestId ? url('contests/' . $currentContestId . '/categories') : url('contests'),
					'Categories' => $currentContestId ? url('contests/' . $currentContestId . '/categories') : url('contests'),
					'Subcategories' => $currentContestId ? url('contests/' . $currentContestId . '/subcategories') : url('contests'),
					'Templates' => url('admin/templates')
				];
				$url = $clickableUrls[$label] ?? null;
				?>
				<?php if ($url): ?>
					<a href="<?= $url ?>" class="stat-card clickable-stat">
						<div class="stat-number"><?= number_format($count) ?></div>
						<div class="stat-label"><?= htmlspecialchars($label) ?></div>
					</a>
				<?php else: ?>
					<div class="stat-card">
						<div class="stat-number"><?= number_format($count) ?></div>
						<div class="stat-label"><?= htmlspecialchars($label) ?></div>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>

	<!-- Currently Logged In -->
	<div class="logged-in-section">
		<div class="section-header">
			<h3>👥 Currently Logged In</h3>
			<div class="refresh-controls">
				<button id="refresh-users" class="refresh-btn" title="Refresh user list">
					<span class="refresh-icon">🔄</span>
					<span class="refresh-text">Refresh</span>
				</button>
				<div class="auto-refresh-toggle">
					<label class="toggle-switch">
						<input type="checkbox" id="auto-refresh" checked>
						<span class="toggle-slider"></span>
					</label>
					<span class="toggle-label">Auto-refresh</span>
				</div>
			</div>
		</div>
		<div class="logged-in-container">
			<div class="logged-in-table">
				<div class="table-header">
					<div class="col-user">User</div>
					<div class="col-role">Role</div>
					<div class="col-ip">IP Address</div>
					<div class="col-status">Status</div>
					<div class="col-time">Last Activity</div>
				</div>
				<div class="logged-in-list" id="active-users-list">
					<?php
					// Get users who are currently logged in (have last_login within last 30 minutes)
					$activeUsers = DB::pdo()->query('
						SELECT DISTINCT u.name, u.email, u.role, u.preferred_name, 
						       u.last_login,
						       al.ip_address
						FROM users u 
						LEFT JOIN activity_logs al ON u.name = al.user_name 
						WHERE u.password_hash IS NOT NULL 
						AND u.last_login IS NOT NULL 
						AND u.last_login > datetime("now", "-30 minutes")
						ORDER BY u.last_login DESC, u.name
					')->fetchAll(\PDO::FETCH_ASSOC);
					?>
					<?php if (empty($activeUsers)): ?>
						<div class="no-active-users">
							<div class="no-users-icon">👤</div>
							<div class="no-users-text">No users currently logged in</div>
						</div>
					<?php else: ?>
						<?php foreach ($activeUsers as $user): ?>
							<div class="logged-in-item" data-user-id="<?= htmlspecialchars($user['name']) ?>">
								<div class="col-user">
									<div class="user-avatar">
										<?php
										$roleIcons = [
											'organizer' => '👑',
											'judge' => '⚖️',
											'contestant' => '🏆',
											'emcee' => '🎤'
										];
										echo $roleIcons[$user['role']] ?? '👤';
										?>
									</div>
									<div class="user-details">
										<div class="user-name"><?= htmlspecialchars($user['preferred_name'] ?: $user['name']) ?></div>
										<?php if ($user['email']): ?>
											<div class="user-email"><?= htmlspecialchars($user['email']) ?></div>
										<?php endif; ?>
									</div>
								</div>
								<div class="col-role">
									<span class="role-badge role-<?= htmlspecialchars($user['role']) ?>">
										<?= htmlspecialchars(ucfirst($user['role'])) ?>
									</span>
								</div>
								<div class="col-ip">
									<?php if ($user['ip_address']): ?>
										<span class="ip-address"><?= htmlspecialchars($user['ip_address']) ?></span>
									<?php else: ?>
										<span class="ip-unknown">Unknown</span>
									<?php endif; ?>
								</div>
								<div class="col-status">
									<?php if ($user['last_login']): ?>
										<div class="status-active">
											<span class="status-indicator active"></span>
											<span class="status-text">Active</span>
										</div>
									<?php else: ?>
										<div class="status-inactive">
											<span class="status-indicator inactive"></span>
											<span class="status-text">Inactive</span>
										</div>
									<?php endif; ?>
								</div>
								<div class="col-time">
									<?php if ($user['last_login']): ?>
										<span class="activity-time" data-timestamp="<?= date('c', strtotime($user['last_login'])) ?>">
											<?= date('M j, g:i A', strtotime($user['last_login'])) ?>
										</span>
									<?php else: ?>
										<span class="no-activity">Never</span>
									<?php endif; ?>
								</div>
							</div>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<div class="logged-in-footer">
			<div class="footer-info">
				<span class="user-count"><?= count($activeUsers) ?> user(s) online</span>
				<span class="last-updated" id="last-updated">Last updated: <?= date('g:i A') ?></span>
			</div>
			<a href="<?= url('admin/users') ?>" class="manage-users">Manage All Users →</a>
		</div>
	</div>

	<!-- Quick Actions -->
	<div class="actions-section">
		<h3>⚡ Quick Actions</h3>
		<div class="action-grid">
			<a href="<?= url('admin/users') ?>" class="action-card">
				<div class="action-icon">👥</div>
				<div class="action-title">Manage Users</div>
				<div class="action-desc">Create and manage all user accounts</div>
			</a>
			
			<a href="<?= url('people') ?>" class="action-card">
				<div class="action-icon">🏆</div>
				<div class="action-title">Contestants & Judges</div>
				<div class="action-desc">Manage contest participants</div>
			</a>
			
			<a href="<?= url('admin/templates') ?>" class="action-card">
				<div class="action-icon">📋</div>
				<div class="action-title">Templates</div>
				<div class="action-desc">Create category templates</div>
			</a>
			
			<a href="<?= url('admin/emcee-scripts') ?>" class="action-card">
				<div class="action-icon">📄</div>
				<div class="action-title">Emcee Scripts</div>
				<div class="action-desc">Upload contest scripts</div>
			</a>
		</div>
	</div>

	<!-- System Management -->
	<div class="system-section">
		<h3>⚙️ System Management</h3>
		<div class="system-grid">
			<a href="<?= url('admin/settings') ?>" class="system-card">
				<div class="system-icon">🔧</div>
				<div class="system-title">Settings</div>
				<div class="system-desc">Configure system preferences</div>
			</a>
			
			<a href="<?= url('admin/logs') ?>" class="system-card">
				<div class="system-icon">📝</div>
				<div class="system-title">Activity Logs</div>
				<div class="system-desc">View system activity and audit trails</div>
			</a>
			
			<a href="<?= url('admin/archived-contests') ?>" class="system-card">
				<div class="system-icon">📦</div>
				<div class="system-title">Archived Contests</div>
				<div class="system-desc">View historical contest data</div>
			</a>
			
			<a href="<?= url('admin/database') ?>" class="system-card">
				<div class="system-icon">🗄️</div>
				<div class="system-title">Database Browser</div>
				<div class="system-desc">Explore database tables and data</div>
			</a>
		</div>
	</div>

	<!-- Recent Activity -->
	<div class="activity-section">
		<h3>🕒 Recent Activity</h3>
		<div class="activity-list">
			<?php
			$recentLogs = DB::pdo()->query('SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 10')->fetchAll(\PDO::FETCH_ASSOC);
			?>
			<?php if (empty($recentLogs)): ?>
				<p class="no-activity">No recent activity</p>
			<?php else: ?>
				<?php foreach ($recentLogs as $log): ?>
					<div class="activity-item">
						<div class="activity-icon">
							<?php
							$icons = [
								'login_success' => '✅',
								'login_failed' => '❌',
								'score_submission' => '📊',
								'user_creation' => '👤',
								'user_deletion' => '🗑️',
								'contest_archive' => '📦'
							];
							echo $icons[$log['action']] ?? '📝';
							?>
						</div>
						<div class="activity-content">
							<div class="activity-action"><?= htmlspecialchars(str_replace('_', ' ', ucfirst($log['action']))) ?></div>
							<div class="activity-user"><?= htmlspecialchars($log['user_name'] ?? 'System') ?> (<?= htmlspecialchars(ucfirst($log['user_role'] ?? 'Unknown')) ?>)</div>
							<div class="activity-time"><?= date('M j, Y g:i A', strtotime($log['created_at'])) ?></div>
						</div>
						<div class="activity-level">
							<span class="log-level-badge log-level-<?= htmlspecialchars($log['log_level'] ?? 'info') ?>">
								<?= htmlspecialchars(strtoupper($log['log_level'] ?? 'info')) ?>
							</span>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<div class="activity-footer">
			<a href="<?= url('admin/logs') ?>" class="view-all-logs">View All Activity Logs →</a>
		</div>
	</div>
</div>

<style>
.dashboard-grid {
	display: grid;
	grid-template-columns: 1fr 1fr;
	grid-template-rows: auto auto auto;
	gap: 20px;
	margin: 20px 0;
}

.stats-section {
	grid-column: 1 / -1;
}

.actions-section {
	grid-column: 1;
}

.system-section {
	grid-column: 2;
}

.logged-in-section {
	grid-column: 1 / -1;
}

.activity-section {
	grid-column: 1 / -1;
}

.stats-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
	gap: 15px;
	margin-top: 15px;
}

.stat-card {
	background: white;
	border: 1px solid #dee2e6;
	border-radius: 8px;
	padding: 20px;
	text-align: center;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.clickable-stat {
	text-decoration: none;
	color: inherit;
	cursor: pointer;
}

.clickable-stat:hover {
	text-decoration: none;
	color: inherit;
	transform: translateY(-2px);
	box-shadow: 0 4px 8px rgba(0,0,0,0.15);
	border-color: #007bff;
}

.stat-number {
	font-size: 2em;
	font-weight: bold;
	color: #007bff;
	margin-bottom: 5px;
}

.stat-label {
	color: #666;
	font-size: 0.9em;
}

.action-grid, .system-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 15px;
	margin-top: 15px;
}

.action-card, .system-card {
	background: white;
	border: 1px solid #dee2e6;
	border-radius: 8px;
	padding: 20px;
	text-decoration: none;
	color: inherit;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
	display: flex;
	flex-direction: column;
	align-items: center;
	text-align: center;
}

.action-card:hover, .system-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 8px rgba(0,0,0,0.15);
	border-color: #007bff;
	text-decoration: none;
	color: inherit;
}

.action-icon, .system-icon {
	font-size: 2em;
	margin-bottom: 10px;
}

.action-title, .system-title {
	font-weight: bold;
	margin-bottom: 5px;
	color: #333;
}

.action-desc, .system-desc {
	font-size: 0.9em;
	color: #666;
}

.activity-list {
	background: white;
	border: 1px solid #dee2e6;
	border-radius: 8px;
	margin-top: 15px;
	max-height: 400px;
	overflow-y: auto;
}

.activity-item {
	display: flex;
	align-items: center;
	padding: 15px 20px;
	border-bottom: 1px solid #f8f9fa;
}

.activity-item:last-child {
	border-bottom: none;
}

.activity-icon {
	font-size: 1.5em;
	margin-right: 15px;
	width: 30px;
	text-align: center;
}

.activity-content {
	flex: 1;
}

.activity-action {
	font-weight: bold;
	color: #333;
	margin-bottom: 2px;
}

.activity-user {
	font-size: 0.9em;
	color: #666;
	margin-bottom: 2px;
}

.activity-time {
	font-size: 0.8em;
	color: #999;
}

.activity-level {
	margin-left: 15px;
}

.log-level-badge {
	padding: 2px 8px;
	border-radius: 12px;
	font-size: 0.7em;
	font-weight: bold;
	color: white;
}

.log-level-debug { background-color: #6c757d; }
.log-level-info { background-color: #17a2b8; }
.log-level-warn { background-color: #ffc107; color: #212529; }
.log-level-error { background-color: #dc3545; }

.activity-footer {
	padding: 15px 20px;
	background: #f8f9fa;
	border-top: 1px solid #dee2e6;
	border-radius: 0 0 8px 8px;
	text-align: center;
}

.view-all-logs {
	color: #007bff;
	text-decoration: none;
	font-weight: bold;
}

.view-all-logs:hover {
	text-decoration: underline;
}

.no-activity {
	padding: 40px 20px;
	text-align: center;
	color: #666;
	font-style: italic;
}

/* Section Header */
.section-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 15px;
}

.refresh-controls {
	display: flex;
	align-items: center;
	gap: 15px;
}

.refresh-btn {
	display: flex;
	align-items: center;
	gap: 5px;
	padding: 8px 12px;
	background: #007bff;
	color: white;
	border: none;
	border-radius: 6px;
	cursor: pointer;
	font-size: 0.9em;
	transition: background-color 0.2s;
}

.refresh-btn:hover {
	background: #0056b3;
}

.refresh-btn:disabled {
	background: #6c757d;
	cursor: not-allowed;
}

.refresh-icon {
	font-size: 1em;
}

.auto-refresh-toggle {
	display: flex;
	align-items: center;
	gap: 8px;
}

.toggle-switch {
	position: relative;
	display: inline-block;
	width: 50px;
	height: 24px;
}

.toggle-switch input {
	opacity: 0;
	width: 0;
	height: 0;
}

.toggle-slider {
	position: absolute;
	cursor: pointer;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background-color: #ccc;
	transition: .4s;
	border-radius: 24px;
}

.toggle-slider:before {
	position: absolute;
	content: "";
	height: 18px;
	width: 18px;
	left: 3px;
	bottom: 3px;
	background-color: white;
	transition: .4s;
	border-radius: 50%;
}

input:checked + .toggle-slider {
	background-color: #007bff;
}

input:checked + .toggle-slider:before {
	transform: translateX(26px);
}

.toggle-label {
	font-size: 0.9em;
	color: #666;
}

/* Table Layout */
.logged-in-container {
	background: white;
	border: 1px solid #dee2e6;
	border-radius: 8px;
	margin-top: 15px;
	overflow: hidden;
}

.logged-in-table {
	display: flex;
	flex-direction: column;
}

.table-header {
	display: grid;
	grid-template-columns: 2fr 1fr 1fr 1fr 1.5fr;
	gap: 15px;
	padding: 15px 20px;
	background: #f8f9fa;
	border-bottom: 1px solid #dee2e6;
	font-weight: bold;
	color: #495057;
	font-size: 0.9em;
}

.logged-in-list {
	max-height: 400px;
	overflow-y: auto;
}

.logged-in-item {
	display: grid;
	grid-template-columns: 2fr 1fr 1fr 1fr 1.5fr;
	gap: 15px;
	padding: 15px 20px;
	border-bottom: 1px solid #f8f9fa;
	align-items: center;
	transition: background-color 0.2s;
}

.logged-in-item:last-child {
	border-bottom: none;
}

.logged-in-item:hover {
	background-color: #f8f9fa;
}

.logged-in-item.new-user {
	background-color: #d4edda;
	animation: highlightNewUser 2s ease-out;
}

@keyframes highlightNewUser {
	0% { background-color: #d4edda; }
	100% { background-color: transparent; }
}

/* Column Styles */
.col-user {
	display: flex;
	align-items: center;
	gap: 12px;
}

.user-avatar {
	font-size: 1.8em;
	width: 40px;
	text-align: center;
}

.user-details {
	flex: 1;
}

.user-name {
	font-weight: bold;
	color: #333;
	margin-bottom: 2px;
	font-size: 0.95em;
}

.user-email {
	font-size: 0.8em;
	color: #666;
}

.col-role {
	display: flex;
	justify-content: center;
}

.role-badge {
	padding: 4px 8px;
	border-radius: 12px;
	font-size: 0.8em;
	font-weight: bold;
	color: white;
}

.role-organizer { background-color: #dc3545; }
.role-judge { background-color: #007bff; }
.role-contestant { background-color: #28a745; }
.role-emcee { background-color: #ffc107; color: #212529; }

.col-ip {
	text-align: center;
}

.ip-address {
	font-family: monospace;
	font-size: 0.85em;
	color: #495057;
	background: #e9ecef;
	padding: 2px 6px;
	border-radius: 4px;
}

.ip-unknown {
	font-size: 0.8em;
	color: #6c757d;
	font-style: italic;
}

.col-status {
	display: flex;
	justify-content: center;
}

.status-active, .status-inactive {
	display: flex;
	align-items: center;
	gap: 6px;
}

.status-indicator {
	width: 8px;
	height: 8px;
	border-radius: 50%;
}

.status-indicator.active {
	background-color: #28a745;
}

.status-indicator.inactive {
	background-color: #6c757d;
}

.status-text {
	font-size: 0.85em;
	font-weight: 500;
	color: #495057;
}

.col-time {
	text-align: center;
}

.activity-time {
	font-size: 0.8em;
	color: #666;
	font-family: monospace;
}

.no-activity {
	font-size: 0.8em;
	color: #6c757d;
	font-style: italic;
}

/* No Users State */
.no-active-users {
	padding: 60px 20px;
	text-align: center;
	color: #666;
}

.no-users-icon {
	font-size: 3em;
	margin-bottom: 10px;
	opacity: 0.5;
}

.no-users-text {
	font-size: 1.1em;
	font-style: italic;
}

.logged-in-footer {
	padding: 15px 20px;
	background: #f8f9fa;
	border-top: 1px solid #dee2e6;
	border-radius: 0 0 8px 8px;
	display: flex;
	justify-content: space-between;
	align-items: center;
}

.footer-info {
	display: flex;
	flex-direction: column;
	gap: 2px;
}

.user-count {
	font-weight: bold;
	color: #495057;
	font-size: 0.9em;
}

.last-updated {
	font-size: 0.8em;
	color: #6c757d;
}

.manage-users {
	color: #007bff;
	text-decoration: none;
	font-weight: bold;
	font-size: 0.9em;
}

.manage-users:hover {
	text-decoration: underline;
}

/* Mobile Responsive */
@media (max-width: 768px) {
	.dashboard-grid {
		grid-template-columns: 1fr;
		grid-template-rows: auto;
	}
	
	.stats-grid {
		grid-template-columns: repeat(2, 1fr);
	}
	
	.action-grid, .system-grid {
		grid-template-columns: 1fr;
	}
	
	.section-header {
		flex-direction: column;
		gap: 10px;
		align-items: flex-start;
	}
	
	.refresh-controls {
		width: 100%;
		justify-content: space-between;
	}
	
	.table-header {
		grid-template-columns: 1fr;
		gap: 5px;
		font-size: 0.8em;
	}
	
	.logged-in-item {
		grid-template-columns: 1fr;
		gap: 10px;
		padding: 12px 15px;
	}
	
	.col-user {
		flex-direction: column;
		text-align: center;
		gap: 8px;
	}
	
	.user-avatar {
		font-size: 2em;
	}
	
	.col-role, .col-ip, .col-status, .col-time {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 5px 0;
		border-bottom: 1px solid #f0f0f0;
	}
	
	.col-role::before { content: "Role: "; font-weight: bold; }
	.col-ip::before { content: "IP: "; font-weight: bold; }
	.col-status::before { content: "Status: "; font-weight: bold; }
	.col-time::before { content: "Last Activity: "; font-weight: bold; }
	
	.logged-in-footer {
		flex-direction: column;
		gap: 10px;
		text-align: center;
	}
}

@media (max-width: 480px) {
	.stats-grid {
		grid-template-columns: 1fr;
	}
	
	.stat-card {
		padding: 15px;
	}
	
	.action-card, .system-card {
		padding: 15px;
	}
	
	.refresh-controls {
		flex-direction: column;
		gap: 10px;
	}
	
	.refresh-btn {
		width: 100%;
		justify-content: center;
	}
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
	const refreshBtn = document.getElementById('refresh-users');
	const autoRefreshToggle = document.getElementById('auto-refresh');
	const activeUsersList = document.getElementById('active-users-list');
	const lastUpdated = document.getElementById('last-updated');
	
	let refreshInterval;
	let isRefreshing = false;
	
	// Auto-refresh every 30 seconds by default
	const REFRESH_INTERVAL = 30000;
	
	// Initialize auto-refresh
	function startAutoRefresh() {
		if (refreshInterval) {
			clearInterval(refreshInterval);
		}
		
		if (autoRefreshToggle.checked) {
			refreshInterval = setInterval(refreshUsers, REFRESH_INTERVAL);
		}
	}
	
	// Stop auto-refresh
	function stopAutoRefresh() {
		if (refreshInterval) {
			clearInterval(refreshInterval);
			refreshInterval = null;
		}
	}
	
	// Refresh users list
	async function refreshUsers() {
		if (isRefreshing) return;
		
		isRefreshing = true;
		refreshBtn.disabled = true;
		refreshBtn.querySelector('.refresh-icon').style.animation = 'spin 1s linear infinite';
		
		try {
			const response = await fetch('/admin/api/active-users');
			if (response.ok) {
				const data = await response.json();
				updateUsersList(data.users);
				updateLastUpdated();
			}
		} catch (error) {
			console.error('Failed to refresh users:', error);
		} finally {
			isRefreshing = false;
			refreshBtn.disabled = false;
			refreshBtn.querySelector('.refresh-icon').style.animation = '';
		}
	}
	
	// Update the users list with new data
	function updateUsersList(users) {
		const currentUsers = Array.from(activeUsersList.querySelectorAll('.logged-in-item')).map(item => 
			item.getAttribute('data-user-id')
		);
		
		// Check for new users
		const newUsers = users.filter(user => !currentUsers.includes(user.name));
		
		// Update or create user items
		users.forEach(user => {
			const existingItem = activeUsersList.querySelector(`[data-user-id="${user.name}"]`);
			
			if (existingItem) {
				// Update existing user
				updateUserItem(existingItem, user);
			} else {
				// Add new user
				const newItem = createUserItem(user);
				activeUsersList.appendChild(newItem);
				
				// Highlight new user
				newItem.classList.add('new-user');
				setTimeout(() => {
					newItem.classList.remove('new-user');
				}, 2000);
			}
		});
		
		// Remove users who are no longer active
		currentUsers.forEach(userId => {
			if (!users.find(user => user.name === userId)) {
				const item = activeUsersList.querySelector(`[data-user-id="${userId}"]`);
				if (item) {
					item.remove();
				}
			}
		});
		
		// Update user count
		const userCount = document.querySelector('.user-count');
		if (userCount) {
			userCount.textContent = `${users.length} user(s) online`;
		}
	}
	
	// Create a new user item
	function createUserItem(user) {
		const item = document.createElement('div');
		item.className = 'logged-in-item';
		item.setAttribute('data-user-id', user.name);
		
		const roleIcons = {
			'organizer': '👑',
			'judge': '⚖️',
			'contestant': '🏆',
			'emcee': '🎤'
		};
		
		const lastActivity = user.last_login ? 
			new Date(user.last_login).toLocaleString('en-US', { 
				month: 'short', 
				day: 'numeric', 
				hour: 'numeric', 
				minute: '2-digit',
				hour12: true 
			}) : 'Never';
		
		item.innerHTML = `
			<div class="col-user">
				<div class="user-avatar">${roleIcons[user.role] || '👤'}</div>
				<div class="user-details">
					<div class="user-name">${escapeHtml(user.preferred_name || user.name)}</div>
					${user.email ? `<div class="user-email">${escapeHtml(user.email)}</div>` : ''}
				</div>
			</div>
			<div class="col-role">
				<span class="role-badge role-${user.role}">${escapeHtml(user.role.charAt(0).toUpperCase() + user.role.slice(1))}</span>
			</div>
			<div class="col-ip">
				${user.ip_address ? `<span class="ip-address">${escapeHtml(user.ip_address)}</span>` : '<span class="ip-unknown">Unknown</span>'}
			</div>
			<div class="col-status">
				<div class="${user.last_login ? 'status-active' : 'status-inactive'}">
					<span class="status-indicator ${user.last_login ? 'active' : 'inactive'}"></span>
					<span class="status-text">${user.last_login ? 'Active' : 'Inactive'}</span>
				</div>
			</div>
			<div class="col-time">
				${user.last_login ? `<span class="activity-time">${lastActivity}</span>` : '<span class="no-activity">Never</span>'}
			</div>
		`;
		
		return item;
	}
	
	// Update an existing user item
	function updateUserItem(item, user) {
		const statusDiv = item.querySelector('.col-status');
		const timeDiv = item.querySelector('.col-time');
		
		// Update status
		if (user.last_login) {
			statusDiv.innerHTML = `
				<div class="status-active">
					<span class="status-indicator active"></span>
					<span class="status-text">Active</span>
				</div>
			`;
		} else {
			statusDiv.innerHTML = `
				<div class="status-inactive">
					<span class="status-indicator inactive"></span>
					<span class="status-text">Inactive</span>
				</div>
			`;
		}
		
		// Update time
		if (user.last_login) {
			const lastActivity = new Date(user.last_login).toLocaleString('en-US', { 
				month: 'short', 
				day: 'numeric', 
				hour: 'numeric', 
				minute: '2-digit',
				hour12: true 
			});
			timeDiv.innerHTML = `<span class="activity-time">${lastActivity}</span>`;
		} else {
			timeDiv.innerHTML = '<span class="no-activity">Never</span>';
		}
	}
	
	// Update last updated timestamp
	function updateLastUpdated() {
		const now = new Date();
		const timeString = now.toLocaleString('en-US', { 
			hour: 'numeric', 
			minute: '2-digit',
			hour12: true 
		});
		lastUpdated.textContent = `Last updated: ${timeString}`;
	}
	
	// Escape HTML to prevent XSS
	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}
	
	// Event listeners
	refreshBtn.addEventListener('click', refreshUsers);
	
	autoRefreshToggle.addEventListener('change', function() {
		if (this.checked) {
			startAutoRefresh();
		} else {
			stopAutoRefresh();
		}
	});
	
	// Start auto-refresh on page load
	startAutoRefresh();
	
	// Clean up on page unload
	window.addEventListener('beforeunload', function() {
		stopAutoRefresh();
	});
});

// CSS for spinning animation
const style = document.createElement('style');
style.textContent = `
	@keyframes spin {
		from { transform: rotate(0deg); }
		to { transform: rotate(360deg); }
	}
`;
document.head.appendChild(style);
</script>