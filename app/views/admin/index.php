<?php use function App\{url, is_organizer}; use App\DB; ?>
<h2>Admin Dashboard</h2>
<p><a href="/">← Back to Home</a></p>

<div class="dashboard-grid">
	<!-- Quick Stats -->
	<div class="stats-section">
		<h3>📊 System Overview</h3>
		<div class="stats-grid">
			<?php
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
				<div class="stat-card">
					<div class="stat-number"><?= number_format($count) ?></div>
					<div class="stat-label"><?= htmlspecialchars($label) ?></div>
				</div>
			<?php endforeach; ?>
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
				<div class="action-desc">Create subcategory templates</div>
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
}
</style>