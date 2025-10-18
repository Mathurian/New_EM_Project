<?php use function App\{url, is_board, home_url}; ?>
<h2>Board Dashboard</h2>
<div class="navigation-buttons">
	<a href="<?= home_url() ?>" class="btn btn-secondary">← Back</a>
	<a href="<?= home_url() ?>" class="btn btn-outline">🏠 Home</a>
</div>

<div class="dashboard-grid">
	<div class="dashboard-card">
		<h3>📊 Certification Status</h3>
		<p>Monitor the certification progress across all levels: Judges, Tally Masters, and Auditors.</p>
		<div class="card-actions">
			<a href="<?= url('board/certification-status') ?>" class="btn btn-primary">View Status</a>
		</div>
	</div>
	
	<div class="dashboard-card">
		<h3>📋 Emcee Scripts</h3>
		<p>Manage contest scripts and announcements for emcees.</p>
		<div class="card-actions">
			<a href="<?= url('board/emcee-scripts') ?>" class="btn btn-primary">Manage Scripts</a>
		</div>
	</div>
	
	<div class="dashboard-card">
		<h3>🖨️ Print Reports</h3>
		<p>Generate and print result reports for contests and categories.</p>
		<div class="card-actions">
			<a href="<?= url('board/print-reports') ?>" class="btn btn-primary">Print Reports</a>
		</div>
	</div>
	
	<div class="dashboard-card">
		<h3>⚖️ Score Management</h3>
		<p>Remove judge scores with proper authorization and co-signatures.</p>
		<div class="card-actions">
			<a href="<?= url('board/remove-judge-scores') ?>" class="btn btn-primary">Manage Scores</a>
		</div>
	</div>
	
	<div class="dashboard-card">
		<h3>📈 Contest Overview</h3>
		<p>View contest and category information.</p>
		<div class="card-stats">
			<div class="stat-item">
				<span class="stat-number"><?= count($dashboardData['contests']) ?></span>
				<span class="stat-label">Contests</span>
			</div>
			<div class="stat-item">
				<span class="stat-number"><?= count($dashboardData['categories']) ?></span>
				<span class="stat-label">Categories</span>
			</div>
		</div>
	</div>
	
	<div class="dashboard-card">
		<h3>✅ Certification Summary</h3>
		<p>Overall certification progress across all subcategories.</p>
		<div class="card-stats">
			<div class="stat-item">
				<span class="stat-number"><?= $dashboardData['judge_certifications'] ?>/<?= $dashboardData['total_subcategories'] ?></span>
				<span class="stat-label">Judge Certified</span>
			</div>
			<div class="stat-item">
				<span class="stat-number"><?= $dashboardData['tally_master_certifications'] ?></span>
				<span class="stat-label">Tally Master Certified</span>
			</div>
			<div class="stat-item">
				<span class="stat-number"><?= $dashboardData['auditor_certifications'] ?></span>
				<span class="stat-label">Auditor Certified</span>
			</div>
		</div>
	</div>
	
	<div class="dashboard-card">
		<h3>👤 My Profile</h3>
		<p>Manage your personal information and password.</p>
		<div class="card-actions">
			<a href="<?= url('my-profile') ?>" class="btn btn-secondary">Edit Profile</a>
		</div>
	</div>
</div>

<style>
.dashboard-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 20px;
	margin: 20px 0;
}

.dashboard-card {
	background: white;
	border: 1px solid #dee2e6;
	border-radius: 8px;
	padding: 20px;
	box-shadow: 0 2px 4px rgba(0,0,0,0.1);
	transition: transform 0.2s, box-shadow 0.2s;
}

.dashboard-card:hover {
	transform: translateY(-2px);
	box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.dashboard-card h3 {
	margin: 0 0 10px 0;
	color: #333;
	font-size: 1.2em;
}

.dashboard-card p {
	margin: 0 0 15px 0;
	color: #666;
	font-size: 0.9em;
	line-height: 1.4;
}

.card-actions {
	margin-top: 15px;
}

.card-actions .btn {
	display: inline-block;
	padding: 8px 16px;
	background: #007bff;
	color: white;
	text-decoration: none;
	border-radius: 4px;
	font-size: 0.9em;
	transition: background-color 0.2s;
}

.card-actions .btn:hover {
	background: #0056b3;
	text-decoration: none;
	color: white;
}

.card-stats {
	display: flex;
	gap: 15px;
	margin-top: 15px;
}

.stat-item {
	text-align: center;
	flex: 1;
}

.stat-number {
	display: block;
	font-size: 1.5em;
	font-weight: bold;
	color: #007bff;
}

.stat-label {
	display: block;
	font-size: 0.8em;
	color: #666;
	margin-top: 2px;
}

@media (max-width: 768px) {
	.dashboard-grid {
		grid-template-columns: 1fr;
		gap: 15px;
	}
	
	.card-stats {
		flex-direction: column;
		gap: 10px;
	}
}
</style>
